<?php

namespace WPFunnels\Import;
use WPFunnels\Wpfnl_functions;
use WPFunnels\Wpfnl;
use WPFunnels\Data_Store\Wpfnl_Steps_Store_Data;
use lementor\TemplateLibrary\Source_Base;
use WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
use Elementor\Plugin;
use WPFunnels\Admin\Notices\Notice as Admin_Notice;

/**
 * This class is responsible import funnel 
 * 
 * Import json file 
 * @since 1.7.5
 */
class Wpfnl_Import {

    /**
     * Option holding the structured status of the current/last import run.
     *
     * Deliberately an option and not a transient: when a job dies, the last record written is
     * the only evidence of what happened, and it has to survive so the status endpoint can
     * report "stalled at funnel 4 of 9" instead of "idle". Always written with autoload off.
     *
     * @since 2.5.16
     */
    const STATUS_OPTION = 'wpfnl_import_status';

    /**
     * Highest export schema version this plugin knows how to read.
     *
     * @since 2.5.16
     */
    const SUPPORTED_SCHEMA_VERSION = 2;

    /**
     * Seconds without a status write, while the action is still queued, before the run is
     * reported as stalled. Generous because a single step can legitimately be slow — the
     * Elementor cache flush is the usual culprit.
     *
     * @since 2.5.16
     */
    const STALL_TIMEOUT = 120;

    /**
     * Seconds a run may sit in the `queued` state before we conclude the queue runner is not
     * firing at all (WP-Cron disabled, no external runner).
     *
     * @since 2.5.16
     */
    const QUEUE_TIMEOUT = 300;

    /**
     * Grace period for "Action Scheduler has nothing queued and nobody wrote recently", which
     * means the worker died mid-run (fatal, OOM kill) rather than finishing.
     *
     * @since 2.5.16
     */
    const VANISH_GRACE = 15;

    /**
     * Minimum seconds between step-level status writes. Bounds `update_option()` churn on a
     * funnel with many steps without making the progress bar look frozen.
     *
     * @since 2.5.16
     */
    const STATUS_WRITE_INTERVAL = 2;

    /**
     * Seconds after which a finished run stops being offered as resumable to a fresh page load.
     *
     * @since 2.5.16
     */
    const STALE_RESULT_AFTER = 600;

    /**
     * Whether an Elementor step was imported and the file cache still needs flushing.
     *
     * @var bool
     * @since 2.5.15
     */
    private $needs_elementor_cache_flush = false;

    /**
     * In-memory status record for the run in progress, flushed to the option by
     * `set_import_status()`. Avoids a read-modify-write round trip per update.
     *
     * @var array
     * @since 2.5.16
     */
    private $status = [];

    /**
     * Unix timestamp of the last status flush, used to throttle step-level writes.
     *
     * @var int
     * @since 2.5.16
     */
    private $status_last_write = 0;

    /**
     * Initialize ajax callback
     *
     * @since 1.7.5
     */
    public function __construct() {
        add_action( 'wp_ajax_wpfnl_import_funnels', [ $this, 'schedule_import_funnels' ] );
        add_action( 'wp_ajax_nopriv_wpfnl_import_funnels', [ $this, 'schedule_import_funnels' ] );
        add_action( 'wp_ajax_wpfnl_hide_import_funnel_notice', [ $this, 'hide_import_funnel_notice' ] );
        add_action( 'wp_ajax_nopriv_wpfnl_hide_import_funnel_notice', [ $this, 'hide_import_funnel_notice' ] );
        // Two accepted args: the job carries a run id alongside the file so a poller can tell
        // its own import from one started elsewhere. Jobs queued before 2.5.16 fire with one
        // argument, which `import_funnels()` handles via its default.
        add_action( 'wpfnl_import_funnels', [ $this, 'import_funnels' ], 10, 2 );
        add_filter( 'wpfnl_notices', [ $this, 'add_funnel_notice' ] );
    }


    /**
     * Read the import status record.
     *
     * The Action Scheduler worker and the polling request are different PHP processes. On a
     * site with a non-shared persistent object cache the reader would otherwise keep serving
     * its own cached copy and the progress bar would sit frozen at 0%, so the cached value is
     * dropped before every read.
     *
     * @return array The status record, or an empty array when no import has ever run.
     * @since 2.5.16
     */
    public static function get_import_status() {
        wp_cache_delete( self::STATUS_OPTION, 'options' );
        $status = get_option( self::STATUS_OPTION, [] );

        return is_array( $status ) ? $status : [];
    }


    /**
     * Merge a patch into the status record and persist it.
     *
     * Every write stamps `updated`, which is what stall detection reads — so a write must
     * happen on every meaningful transition, not only when a counter changes.
     *
     * @param array $patch Keys to merge into the current record.
     *
     * @return void
     * @since 2.5.16
     */
    private function set_import_status( $patch ) {
        if( !is_array( $patch ) ) {
            return;
        }

        $this->status = array_merge( $this->status, $patch );
        $this->status[ 'updated' ] = time();

        $total = isset( $this->status[ 'total_steps' ] ) ? (int) $this->status[ 'total_steps' ] : 0;
        $done  = isset( $this->status[ 'done_steps' ] ) ? (int) $this->status[ 'done_steps' ] : 0;

        // Step counts give a smoother bar than funnel counts; fall back to funnels when the
        // payload carried no steps at all.
        if( $total < 1 ) {
            $total = isset( $this->status[ 'total_funnels' ] ) ? (int) $this->status[ 'total_funnels' ] : 0;
            $done  = isset( $this->status[ 'done_funnels' ] ) ? (int) $this->status[ 'done_funnels' ] : 0;
        }

        $this->status[ 'pct' ] = $total > 0 ? min( 100, (int) floor( ( $done / $total ) * 100 ) ) : 0;

        update_option( self::STATUS_OPTION, $this->status, false );
        $this->status_last_write = time();
    }


    /**
     * Persist the status record only if the throttle window has elapsed.
     *
     * Used for per-step updates, where an unthrottled write would mean one `update_option()`
     * call per step.
     *
     * @param array $patch Keys to merge into the current record.
     * @param bool  $force Write regardless of the throttle.
     *
     * @return void
     * @since 2.5.16
     */
    private function maybe_set_import_status( $patch, $force = false ) {
        if( $force || ( time() - $this->status_last_write ) >= self::STATUS_WRITE_INTERVAL ) {
            $this->set_import_status( $patch );
            return;
        }

        // Keep the in-memory record current so the next flush carries the latest counters.
        $this->status = array_merge( $this->status, is_array( $patch ) ? $patch : [] );
    }


    /**
     * Normalise a decoded import payload into `[ 'meta' => ..., 'funnels' => ... ]`.
     *
     * Exports before 2.5.16 were a bare top-level array of funnels; from schema 2 they are an
     * envelope object carrying provenance plus a `funnels` list. Both are accepted forever.
     *
     * Detection keys on `funnels` rather than `schema_version` for two reasons: `funnels` is
     * the key actually needed, so a hand-edited file missing the version still imports; and a
     * legacy payload can never carry a string key `funnels` because it is a list keyed 0..n.
     *
     * @param mixed $decoded Decoded JSON payload.
     *
     * @return array|\WP_Error `[ 'meta' => array, 'funnels' => array ]` or a WP_Error.
     * @since 2.5.16
     */
    public static function normalize_import_payload( $decoded ) {
        if( !is_array( $decoded ) || empty( $decoded ) ) {
            return new \WP_Error( 'invalid_structure', __( 'This file is not a valid WPFunnels export.', 'wpfnl' ) );
        }

        if( isset( $decoded[ 'funnels' ] ) && is_array( $decoded[ 'funnels' ] ) ) {
            $schema = isset( $decoded[ 'schema_version' ] ) ? (int) $decoded[ 'schema_version' ] : self::SUPPORTED_SCHEMA_VERSION;
            $meta   = [
                'schema_version' => $schema,
                'generator'      => isset( $decoded[ 'generator' ] ) ? sanitize_text_field( $decoded[ 'generator' ] ) : '',
                'plugin_version' => isset( $decoded[ 'plugin_version' ] ) ? sanitize_text_field( $decoded[ 'plugin_version' ] ) : '',
                'pro_version'    => isset( $decoded[ 'pro_version' ] ) ? sanitize_text_field( $decoded[ 'pro_version' ] ) : '',
                'exported_at'    => isset( $decoded[ 'exported_at' ] ) ? sanitize_text_field( $decoded[ 'exported_at' ] ) : '',
                'source_site'    => isset( $decoded[ 'source_site' ] ) ? esc_url_raw( $decoded[ 'source_site' ] ) : '',
                'legacy'         => false,
                // A newer file warns but still imports: the format is additive and per-funnel
                // objects are read key by key, so unknown keys are simply ignored. Blocking
                // would strand people mid-migration for no safety benefit.
                'forward_compat' => $schema > self::SUPPORTED_SCHEMA_VERSION,
            ];
            $funnels = array_values( $decoded[ 'funnels' ] );
        }
        else {
            $meta    = [
                'schema_version' => 1,
                'generator'      => '',
                'plugin_version' => '',
                'pro_version'    => '',
                'exported_at'    => '',
                'source_site'    => '',
                'legacy'         => true,
                'forward_compat' => false,
            ];
            $funnels = array_values( $decoded );
        }

        if( empty( $funnels ) ) {
            return new \WP_Error( 'invalid_structure', __( 'This file is not a valid WPFunnels export.', 'wpfnl' ) );
        }

        foreach( $funnels as $funnel ) {
            if( !is_array( $funnel ) ) {
                return new \WP_Error( 'invalid_structure', __( 'This file is not a valid WPFunnels export.', 'wpfnl' ) );
            }
            if( empty( $funnel[ 'funnel_id' ] ) ) {
                return new \WP_Error( 'invalid_structure', __( 'This file is not a valid WPFunnels export.', 'wpfnl' ) );
            }
            if( !isset( $funnel[ 'funnel_meta' ] ) || !is_array( $funnel[ 'funnel_meta' ] ) ) {
                return new \WP_Error( 'invalid_structure', __( 'This file is not a valid WPFunnels export.', 'wpfnl' ) );
            }
            if( !isset( $funnel[ 'steps_data' ] ) || !is_array( $funnel[ 'steps_data' ] ) ) {
                return new \WP_Error( 'invalid_structure', __( 'This file is not a valid WPFunnels export.', 'wpfnl' ) );
            }
        }

        return [
            'meta'    => $meta,
            'funnels' => $funnels,
        ];
    }


    /**
     * Derive the effective state of a status record.
     *
     * Stall detection has to live in the reader: the process that would have marked itself
     * stalled is precisely the one that died. This is a pure derivation — safe to run on every
     * poll and from concurrent requests.
     *
     * @param array $record Status record from `get_import_status()`.
     *
     * @return array `[ 'state' => string, 'reason' => string ]`.
     * @since 2.5.16
     */
    public static function derive_import_state( $record ) {
        if( empty( $record ) || empty( $record[ 'state' ] ) ) {
            return [ 'state' => 'idle', 'reason' => '' ];
        }

        if( in_array( $record[ 'state' ], [ 'completed', 'failed' ], true ) ) {
            return [ 'state' => $record[ 'state' ], 'reason' => '' ];
        }

        // Arg-less on purpose: this matches any pending `wpfnl_import_funnels` action, which is
        // what makes it a global lock. Passing args would break the lock semantics.
        $scheduled = function_exists( 'as_has_scheduled_action' ) ? as_has_scheduled_action( 'wpfnl_import_funnels' ) : false;
        $age       = time() - ( isset( $record[ 'updated' ] ) ? (int) $record[ 'updated' ] : 0 );

        if( !$scheduled && $age > self::VANISH_GRACE ) {
            return [ 'state' => 'stalled', 'reason' => 'job_vanished' ];
        }

        if( $scheduled && 'queued' === $record[ 'state' ] && $age > self::QUEUE_TIMEOUT ) {
            return [ 'state' => 'stalled', 'reason' => 'never_started' ];
        }

        if( $scheduled && $age > self::STALL_TIMEOUT ) {
            return [ 'state' => 'stalled', 'reason' => 'no_progress' ];
        }

        return [ 'state' => 'running', 'reason' => '' ];
    }


    /**
     * Build the payload served by the import-status REST route.
     *
     * @param string $run_id Run id the caller believes it is watching. When it does not match
     *                       the stored record the caller is told its run was superseded, so it
     *                       stops polling rather than narrating somebody else's import.
     *
     * @return array
     * @since 2.5.16
     */
    public static function get_status_payload( $run_id = '' ) {
        $record = self::get_import_status();

        if( empty( $record ) ) {
            return [ 'state' => 'idle', 'run_id' => '', 'pct' => 0, 'label' => '' ];
        }

        $stored_run_id = isset( $record[ 'run_id' ] ) ? $record[ 'run_id' ] : '';

        if( $run_id && $stored_run_id && $run_id !== $stored_run_id ) {
            return [ 'state' => 'superseded', 'run_id' => $stored_run_id, 'pct' => 0, 'label' => '' ];
        }

        $derived = self::derive_import_state( $record );
        $updated = isset( $record[ 'updated' ] ) ? (int) $record[ 'updated' ] : 0;

        return [
            'state'         => $derived[ 'state' ],
            'reason'        => $derived[ 'reason' ],
            'run_id'        => $stored_run_id,
            'pct'           => isset( $record[ 'pct' ] ) ? (int) $record[ 'pct' ] : 0,
            'label'         => isset( $record[ 'label' ] ) ? $record[ 'label' ] : '',
            'message'       => isset( $record[ 'message' ] ) ? $record[ 'message' ] : '',
            'total_funnels' => isset( $record[ 'total_funnels' ] ) ? (int) $record[ 'total_funnels' ] : 0,
            'done_funnels'  => isset( $record[ 'done_funnels' ] ) ? (int) $record[ 'done_funnels' ] : 0,
            'total_steps'   => isset( $record[ 'total_steps' ] ) ? (int) $record[ 'total_steps' ] : 0,
            'done_steps'    => isset( $record[ 'done_steps' ] ) ? (int) $record[ 'done_steps' ] : 0,
            'imported'      => isset( $record[ 'imported' ] ) ? array_values( (array) $record[ 'imported' ] ) : [],
            'failed'        => isset( $record[ 'failed' ] ) ? array_values( (array) $record[ 'failed' ] ) : [],
            'source'        => isset( $record[ 'source' ] ) ? (array) $record[ 'source' ] : [],
            'funnels_url'   => add_query_arg( [ 'page' => WPFNL_FUNNEL_PAGE_SLUG ], admin_url( 'admin.php' ) ),
            // A fresh page load uses this to decide between resuming a live poll and showing a
            // dismissible result card for a run that finished a while ago.
            'is_stale'      => in_array( $derived[ 'state' ], [ 'completed', 'failed' ], true )
                               && ( time() - $updated ) > self::STALE_RESULT_AFTER,
        ];
    }


    /**
     * Clear a stuck import so the user is not locked out.
     *
     * Without this, a single failed run leaves `as_has_scheduled_action()` returning true
     * forever and every later import is refused with "Import already scheduled".
     *
     * @return array
     * @since 2.5.16
     */
    public static function reset_import() {
        if( function_exists( 'as_unschedule_all_actions' ) ) {
            as_unschedule_all_actions( 'wpfnl_import_funnels' );
        }

        $record = self::get_import_status();

        if( !empty( $record[ 'file' ] ) ) {
            self::delete_import_file( $record[ 'file' ] );
        }

        delete_option( self::STATUS_OPTION );
        delete_option( 'wpfnl_import_notice' );

        return [ 'success' => true ];
    }


    /**
     * Delete a file, but only when it really sits inside the import directory.
     *
     * The path comes from a stored record, so it is treated as untrusted: a poisoned record
     * must not be able to unlink arbitrary files.
     *
     * @param string $file Absolute path to remove.
     *
     * @return bool Whether the file was removed.
     * @since 2.5.16
     */
    private static function delete_import_file( $file ) {
        $upload = wp_upload_dir();
        $base   = realpath( trailingslashit( $upload[ 'basedir' ] ) . 'wpfunnels/import' );
        $target = realpath( $file );

        if( !$base || !$target ) {
            return false;
        }

        if( 0 !== strpos( $target, $base . DIRECTORY_SEPARATOR ) ) {
            return false;
        }

        return unlink( $target );
    }

    /**
     * Adds a custom admin notice to the WordPress admin dashboard.
     *
     * This function is a callback used to add a custom notice to the admin dashboard.
     * The notice is added to the existing array of notices.
     *
     * @param array $notices The array of existing admin notices.
     *
     * @return array The updated array of admin notices with the added custom notice.
     * @since 1.9.7
     */
    public function add_funnel_notice( $notices ) {
        $notices[ 'WPFunnels\Import\Wpfnl_Import' ]= 'funnel_import_notice';
        return $notices;
    }

    /**
     * Displays a notice related to funnel import status.
     *
     * This static method is used to display a notice related to the funnel import status in the WordPress admin area.
     * It checks whether the import status option is not empty and whether the current screen context allows displaying
     * the notice. If the conditions are met, a notice is generated using the provided options and displayed in the admin area.
     * The notice can be dismissed by the user.
     *
     * @since 1.9.7
     */
    public static function funnel_import_notice() {
        $import_status = get_option( 'wpfnl_import_notice', '' );
        if( !empty( $import_status ) ) {
            global $current_screen;
            // `$current_screen` is not set on every admin request that reaches admin_notices,
            // so read `parent_base` defensively rather than off a possible null.
            $parent_base          = isset( $current_screen->parent_base ) ? $current_screen->parent_base : '';
            $current_page         = isset( $_GET[ 'page' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'page' ] ) ) : '';
            $disabled_notice_page = [ 'wp_funnels' ];
            if( 'plugins' === $parent_base || '' === $current_page || in_array( $current_page, $disabled_notice_page, true ) ) {
                $nonce_field = "<input type='hidden' id='wpfnl-import-nonce' value='" . esc_attr( wp_create_nonce('wpfnl-admin') ) . "'>";
                $options = array(
                    'id'          => 'wpfnl-import-notice',
                    'title'       => 'wpfunnels-basic',
                    'description' => "<span>{$import_status}</span>{$nonce_field}",
                    'classes'     => [ 'notice', 'notice-info', 'wpfnl-import-notice' ],
                    'type'        => 'update-plugin',
                    'dismissible' => true,
                    'icon'        => '',
                );
                Admin_Notice::print_notice( $options );
            }
        }
    }

    /**
     * Schedule the import of funnels as a background process.
     *
     * This method is responsible for scheduling the import of funnels as a background process. It checks if the 'wpfnl_import_funnels'
     * action has not already been scheduled, and if so, it prepares the necessary file for import and schedules the background process.
     * The process involves uploading the imported file, updating the import notice, and enqueueing the 'wpfnl_import_funnels' action
     * to be executed asynchronously using a background processing mechanism (such as WP Async Task).
     *
     * @since 1.9.7
     * @updated 2.5.14 - Added security checks
     */
    public function schedule_import_funnels() {
        // Security: Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized access.', 'wpfnl' ) ], 401 );
            wp_die();
        }

        // Security: Check user capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to import funnels.', 'wpfnl' ) ], 403 );
            wp_die();
        }

        // Security: Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpfnl_import_funnels_nonce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'wpfnl' ) ], 403 );
            wp_die();
        }

        $result = $this->schedule_import();

        if( is_wp_error( $result ) ) {
            $data   = (array) $result->get_error_data();
            $status = isset( $data[ 'status' ] ) ? (int) $data[ 'status' ] : 400;
            unset( $data[ 'status' ] );

            wp_send_json_error(
                array_merge(
                    [
                        'code'    => $result->get_error_code(),
                        'message' => $result->get_error_message(),
                    ],
                    $data
                ),
                $status
            );
            wp_die();
        }

        wp_send_json_success( $result );
        wp_die();
    }


    /**
     * Queue an uploaded payload for import and seed its status record.
     *
     * Shared by the admin-ajax handler above and the REST route the React funnel list posts
     * to, so both entry points enforce the same lock, upload the file the same way and report
     * the same run id. Callers are responsible for their own capability and nonce checks —
     * this method does the work, not the gatekeeping.
     *
     * @return array|\WP_Error `[ 'message' => string, 'run_id' => string ]`, or an error whose
     *                        data carries the HTTP `status` to answer with.
     * @since 3.13.4
     */
    public function schedule_import() {
        // A previous run that died leaves its action pending forever, which used to refuse
        // every later import with "Import already scheduled" and no way out. Clear the lock
        // when the stored record says the run is stalled; only refuse when it is genuinely
        // still moving, and then tell the client it may reset.
        if( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( 'wpfnl_import_funnels' ) ) {
            $derived = self::derive_import_state( self::get_import_status() );

            if( 'running' === $derived[ 'state' ] ) {
                $record = self::get_import_status();

                return new \WP_Error(
                    'import_in_progress',
                    __( 'An import is already running. Wait for it to finish, or cancel it to start a new one.', 'wpfnl' ),
                    [
                        'status'    => 409,
                        'run_id'    => isset( $record[ 'run_id' ] ) ? $record[ 'run_id' ] : '',
                        'can_reset' => true,
                    ]
                );
            }

            self::reset_import();
        }

        $file = $this->upload_imported_file();

        if( is_wp_error( $file ) ) {
            return new \WP_Error( $file->get_error_code(), $file->get_error_message(), [ 'status' => 400 ] );
        }

        if( !file_exists( $file ) ) {
            return new \WP_Error( 'upload_failed', __( 'Failed to upload file.', 'wpfnl' ), [ 'status' => 400 ] );
        }

        $run_id  = uniqid( 'imp_', true );
        $totals  = $this->count_payload_totals( $file );

        // Seed the record before enqueuing so a poll that arrives before the worker starts
        // still finds a run to report on.
        $this->status            = [];
        $this->status_last_write = 0;
        $this->set_import_status(
            [
                'run_id'        => $run_id,
                'state'         => 'queued',
                'started'       => time(),
                'file'          => $file,
                'user_id'       => get_current_user_id(),
                'source'        => $totals[ 'source' ],
                'total_funnels' => $totals[ 'funnels' ],
                'done_funnels'  => 0,
                'total_steps'   => $totals[ 'steps' ],
                'done_steps'    => 0,
                'label'         => __( 'Preparing import…', 'wpfnl' ),
                'message'       => '',
                'imported'      => [],
                'failed'        => [],
            ]
        );

        update_option( 'wpfnl_import_notice', __( 'Funnels import in-progress...', 'wpfnl' ) );
        as_enqueue_async_action( 'wpfnl_import_funnels', [ $file, $run_id ], 'wpfunnels-import' );

        return [
            'message' => __( 'Funnel import scheduled successfully.', 'wpfnl' ),
            'run_id'  => $run_id,
        ];
    }


    /**
     * Count the funnels and steps in an uploaded payload so progress has a denominator.
     *
     * @param string $file Absolute path to the validated import file.
     *
     * @return array `[ 'funnels' => int, 'steps' => int, 'source' => array ]`.
     * @since 2.5.16
     */
    private function count_payload_totals( $file ) {
        $empty = [ 'funnels' => 0, 'steps' => 0, 'source' => [] ];

        if( !file_exists( $file ) ) {
            return $empty;
        }

        $decoded = json_decode( file_get_contents( $file ), true );
        $payload = self::normalize_import_payload( $decoded );

        if( is_wp_error( $payload ) ) {
            return $empty;
        }

        $steps = 0;
        foreach( $payload[ 'funnels' ] as $funnel ) {
            $steps += isset( $funnel[ 'steps_data' ] ) ? count( $funnel[ 'steps_data' ] ) : 0;
        }

        return [
            'funnels' => count( $payload[ 'funnels' ] ),
            'steps'   => $steps,
            'source'  => $payload[ 'meta' ],
        ];
    }

    /**
     * Uploads an imported file to the specified directory in the WordPress uploads folder.
     *
     * This private method is used to upload an imported file to a specific directory within the WordPress uploads folder.
     * It ensures that the target directory exists and creates it if not present. The method reads the temporary uploaded
     * file, extracts its content, and saves it to the target file location. The saved file's name is based on the original
     * uploaded filename, and if no filename is provided, a default name is used.
     *
     * @return string|WP_Error The full path to the uploaded file on success, or WP_Error on failure.
     * @since 1.9.7
     * @updated 2.5.14 - Added comprehensive security validation
     */
    private function upload_imported_file() {
        // Security: Validate file upload exists
        if ( ! isset( $_FILES['uploaded_file'] ) || ! isset( $_FILES['uploaded_file']['tmp_name'] ) ) {
            return new \WP_Error( 'no_file', __( 'No file uploaded.', 'wpfnl' ) );
        }

        // Security: Check for upload errors
        if ( $_FILES['uploaded_file']['error'] !== UPLOAD_ERR_OK ) {
            return new \WP_Error( 'upload_error', __( 'File upload failed.', 'wpfnl' ) );
        }

        $temp_file = sanitize_text_field( wp_unslash( $_FILES['uploaded_file']['tmp_name'] ) );
        $file_name = sanitize_file_name( wp_unslash( $_FILES['uploaded_file']['name'] ) );
        $file_size = intval( $_FILES['uploaded_file']['size'] );

        // Security: Validate file is not empty
        if ( empty( $temp_file ) || ! file_exists( $temp_file ) ) {
            return new \WP_Error( 'invalid_file', __( 'Invalid file upload.', 'wpfnl' ) );
        }

        // Security: Validate file size (max 10MB)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ( $file_size > $max_size || $file_size === 0 ) {
            return new \WP_Error( 'file_too_large', __( 'File size must be between 1 byte and 10MB.', 'wpfnl' ) );
        }

        // Security: Validate file extension
        $file_ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
        if ( $file_ext !== 'json' ) {
            return new \WP_Error( 'invalid_extension', __( 'Only JSON files are allowed.', 'wpfnl' ) );
        }

        // Security: Validate MIME type
        $file_type = wp_check_filetype( $file_name, [ 'json' => 'application/json' ] );
        if ( ! $file_type['ext'] ) {
            return new \WP_Error( 'invalid_mime', __( 'Invalid file type. Only JSON files are allowed.', 'wpfnl' ) );
        }

        // Security: Read and validate JSON content
        $content = file_get_contents( $temp_file );
        if ( $content === false ) {
            return new \WP_Error( 'read_error', __( 'Failed to read file content.', 'wpfnl' ) );
        }

        // Security: Validate JSON structure
        $decoded = json_decode( $content, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new \WP_Error( 'invalid_json', __( 'Invalid JSON format.', 'wpfnl' ) );
        }

        // Security: Check for PHP open tags in content.
        // NOTE: `<script` and `javascript:` are deliberately NOT checked here. Funnel steps
        // legitimately carry marketing tags (Meta Pixel, gtag) and page-builder link fields
        // inside their page content, and scanning the raw payload for them rejected valid
        // exports. That content is stored as post data and is never executed by the importer.
        if ( preg_match( '/<\?php|<\?=/i', $content ) ) {
            return new \WP_Error( 'php_code', __( 'File contains PHP code and cannot be imported.', 'wpfnl' ) );
        }

        // Security: Confirm the payload is actually a funnel export rather than arbitrary JSON.
        // Accepts both the schema 2 envelope and the legacy bare array.
        $payload = self::normalize_import_payload( $decoded );
        if ( is_wp_error( $payload ) ) {
            return $payload;
        }

        $path = wp_upload_dir();
        $path = $path['basedir'] . '/wpfunnels/import/';

        // Make directory if not exist
        if ( ! file_exists( $path ) ) {
            wp_mkdir_p( $path );
        }

        // Security: Use unique filename to prevent overwrites
        $unique_filename = wp_unique_filename( $path, $file_name );
        $file_path = $path . $unique_filename;

        // Security: Write file with restricted permissions
        $bytes_written = file_put_contents( $file_path, $content );
        if ( $bytes_written === false ) {
            return new \WP_Error( 'write_error', __( 'Failed to save file.', 'wpfnl' ) );
        }

        // Security: Set proper file permissions (read/write for owner only)
        chmod( $file_path, 0600 );

        return $file_path;
    }


    /**
     * Unserialize an imported meta value without instantiating PHP objects.
     *
     * Behaves like `maybe_unserialize()` but passes `allowed_classes => false`, so a crafted
     * `O:` payload in an import file resolves to `__PHP_Incomplete_Class` instead of a real
     * object. That blocks PHP object injection (POP chains via `__wakeup`/`__destruct`),
     * which is the one genuinely executable path in the import flow.
     *
     * @param mixed $original The value to unserialize.
     *
     * @return mixed The unserialized value, or the original value when it is not serialized.
     * @since 2.5.15
     */
    private function safe_maybe_unserialize( $original ) {
        if ( ! is_string( $original ) ) {
            return $original;
        }

        $trimmed = trim( $original );
        if ( ! is_serialized( $trimmed ) ) {
            return $original;
        }

        $result = @unserialize( $trimmed, [ 'allowed_classes' => false ] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

        // `unserialize()` returns false both on failure and for the serialized value `false`.
        if ( false === $result && 'b:0;' !== $trimmed ) {
            return $original;
        }

        return $result;
    }


    /**
     * Determine which page builder a step was actually built with.
     *
     * Resolved from the step's own exported meta rather than `get_builder_type()`, which reads
     * the destination site's WPFunnels setting (default `gutenberg`) and says nothing about the
     * funnel being imported. Relying on that setting meant an Elementor funnel imported into a
     * site that had not switched its builder skipped Elementor's post-import pass entirely,
     * leaving images unmapped and the source site's render caches in place.
     *
     * Mirrors the detection order already used by `Wpfnl_functions::get_page_builder_by_step_id()`.
     *
     * @param array $step_data Exported step payload, expected to carry a `meta` array.
     *
     * @return string Builder slug, falling back to the site setting when nothing is detected.
     * @since 2.5.15
     */
    private function detect_step_builder( $step_data ) {
        $meta = isset( $step_data['meta'] ) && is_array( $step_data['meta'] ) ? $step_data['meta'] : [];

        $value = function( $key ) use ( $meta ) {
            return isset( $meta[ $key ][0] ) ? $meta[ $key ][0] : '';
        };

        if ( ! empty( $value( '_elementor_edit_mode' ) ) || ! empty( $value( '_elementor_data' ) ) ) {
            return 'elementor';
        }
        if ( 'on' === $value( '_et_pb_use_builder' ) ) {
            return 'divi-builder';
        }
        if ( 'bricks' === $value( '_bricks_editor_mode' ) ) {
            return 'bricks';
        }
        if ( ! empty( $value( 'ct_builder_shortcodes' ) ) ) {
            return 'oxygen';
        }

        return Wpfnl_functions::get_builder_type();
    }


    /**
     * Run the page-builder specific post-import pass for a freshly created step.
     *
     * For Elementor this is what remaps media through `Control_Media::on_import()`, pulling
     * remote images into this site's library instead of leaving them pointed at the source.
     *
     * @param int    $step_id      Newly created step id.
     * @param string $builder_type Builder slug from `detect_step_builder()`.
     *
     * @return void
     * @since 2.5.15
     */
    private function run_builder_post_import( $step_id, $builder_type ) {
        // Every source below fetches remote assets while processing the template.
        if ( ! ini_get( 'allow_url_fopen' ) ) {
            return;
        }

        if ( 'gutenberg' === $builder_type && class_exists( '\WPFunnels\Batch\Gutenberg\Wpfnl_Gutenberg_Source' ) ) {
            $gutenberg_source = new \WPFunnels\Batch\Gutenberg\Wpfnl_Gutenberg_Source;
            $gutenberg_source->import_single_template( $step_id );
        }

        if ( 'elementor' === $builder_type && class_exists( '\Elementor\Plugin' ) && class_exists( '\WPFunnels\Batch\Elementor\Wpfnl_Elementor_Source' ) ) {
            $elementor_source = new \WPFunnels\Batch\Elementor\Wpfnl_Elementor_Source;
            $elementor_source->import_single_template( $step_id );
            // Flushed once after the whole import instead of per step; clear_cache() globs and
            // unlinks the entire Elementor CSS directory on every call.
            $this->needs_elementor_cache_flush = true;
        }

        if ( 'divi-builder' === $builder_type && class_exists( '\WPFunnels\Batch\Divi\Wpfnl_Divi_Source' ) ) {
            $divi_source = new \WPFunnels\Batch\Divi\Wpfnl_Divi_Source;
            $divi_source->import_single_template( $step_id );
        }

        if ( 'oxygen' === $builder_type && class_exists( '\WPFunnels\Batch\Oxygen\Wpfnl_Oxygen_Source' ) ) {
            $oxygen_source = new \WPFunnels\Batch\Oxygen\Wpfnl_Oxygen_Source;
            $oxygen_source->import_single_template( $step_id );
        }
    }


    /**
     * Import Funnel data and create
     *
     * @param string $file   Absolute path to the uploaded import file.
     * @param string $run_id Run id minted when the job was scheduled. Empty for jobs queued by
     *                       a version before 2.5.16, which fire this hook with one argument —
     *                       in that case a run id is minted here so the UI is not left blind.
     *
     * @return true
     * @since  1.7.5
     */
    public function import_funnels( $file, $run_id = '' ) {
        $file_data = [];
        if( file_exists( $file ) ) {
            $file_data = json_decode( file_get_contents( $file ), true );
            unlink( $file );
        }
        $failed = false;

        $payload = self::normalize_import_payload( $file_data );

        if( is_wp_error( $payload ) ) {
            $this->finish_import( $run_id, true, $payload->get_error_message() );
            return true;
        }

        $file_data = $payload[ 'funnels' ];

        // Adopt the record seeded at schedule time, or start one when this job predates the
        // run-id argument. Either way the worker owns the record from here on.
        $this->status = self::get_import_status();
        if( !$run_id || empty( $this->status[ 'run_id' ] ) || $this->status[ 'run_id' ] !== $run_id ) {
            $run_id       = $run_id ? $run_id : uniqid( 'imp_', true );
            $this->status = [
                'run_id'   => $run_id,
                'started'  => time(),
                'file'     => $file,
                'user_id'  => get_current_user_id(),
                'source'   => $payload[ 'meta' ],
                'imported' => [],
                'failed'   => [],
            ];
        }

        $total_steps = 0;
        foreach( $file_data as $data ) {
            $total_steps += isset( $data[ 'steps_data' ] ) ? count( $data[ 'steps_data' ] ) : 0;
        }

        $done_funnels   = 0;
        $done_steps     = 0;
        $failed_funnels = [];
        // Per-cap latch: once the free plan's limit is reached, every later funnel of that
        // type is skipped without re-running the count query.
        $limit_hit      = [ 'funnel' => false, 'store_checkout' => false ];

        $this->set_import_status(
            [
                'state'         => 'running',
                'total_funnels' => count( $file_data ),
                'done_funnels'  => 0,
                'total_steps'   => $total_steps,
                'done_steps'    => 0,
                'label'         => __( 'Starting import…', 'wpfnl' ),
            ]
        );

        if( is_array( $file_data ) ) {
            $is_pro = apply_filters( 'wpfunnels/is_pro_license_activated', false );
            foreach( $file_data as $data ) {
                // normalize_import_payload() already rejects a payload with a funnel missing
                // its id, so this is belt-and-braces — record and skip rather than abort.
                if( empty( $data[ 'funnel_id' ] ) ) {
                    $failed         = true;
                    $failed_funnels = $this->append_failure( $failed_funnels, $data, 'invalid_funnel_data', __( 'Invalid funnel data.', 'wpfnl' ) );
                    $this->set_import_status( [ 'failed' => $failed_funnels ] );
                    continue;
                }

                $this->set_import_status(
                    [
                        'label' => sprintf(
                            /* translators: 1: funnel name, 2: current funnel number, 3: total funnels */
                            __( 'Importing "%1$s" (%2$d of %3$d)…', 'wpfnl' ),
                            isset( $data[ 'funnel_name' ] ) ? $data[ 'funnel_name' ] : __( 'Untitled', 'wpfnl' ),
                            $done_funnels + 1,
                            count( $file_data )
                        ),
                    ]
                );

                // Free-plan caps skip this funnel rather than aborting the run, so the rest
                // of the file still imports. Once a cap is hit every later funnel of that type
                // hits it too, so the count query is not repeated.
                if( !$is_pro ) {
                    $imported_type   = isset( $data['funnel_meta']['_wpfnl_funnel_type'][0] ) ? $data['funnel_meta']['_wpfnl_funnel_type'][0] : '';
                    $is_importing_sc = ( 'store_checkout' === $imported_type );
                    $cap_key         = $is_importing_sc ? 'store_checkout' : 'funnel';

                    if( !$limit_hit[ $cap_key ] ) {
                        $current_count = $is_importing_sc
                            ? \WPFunnels\Wpfnl_functions::count_store_checkout_funnels()
                            : \WPFunnels\Wpfnl_functions::count_non_store_checkout_funnels();

                        if( $current_count >= 3 ) {
                            $limit_hit[ $cap_key ] = true;
                        }
                    }

                    if( $limit_hit[ $cap_key ] ) {
                        $failed         = true;
                        $failed_funnels = $this->append_failure(
                            $failed_funnels,
                            $data,
                            $is_importing_sc ? 'free_limit_store_checkouts' : 'free_limit_funnels',
                            $is_importing_sc
                                ? __( 'Skipped: the free plan allows 3 store checkouts.', 'wpfnl' )
                                : __( 'Skipped: the free plan allows 3 funnels.', 'wpfnl' )
                        );
                        $this->set_import_status( [ 'failed' => $failed_funnels ] );
                        continue;
                    }
                }

                try {
                    $result = $this->import_single_funnel( $data, $done_steps );
                }
                catch( \Throwable $e ) {
                    // One malformed funnel must not take the whole file down with it.
                    $failed         = true;
                    $failed_funnels = $this->append_failure( $failed_funnels, $data, 'exception', $e->getMessage() );
                    $this->set_import_status( [ 'failed' => $failed_funnels ] );
                    continue;
                }

                if( is_wp_error( $result ) ) {
                    $failed         = true;
                    $failed_funnels = $this->append_failure( $failed_funnels, $data, $result->get_error_code(), $result->get_error_message() );
                    $this->set_import_status( [ 'failed' => $failed_funnels ] );
                    continue;
                }

                if( $result[ 'failed_steps' ] > 0 ) {
                    $failed = true;
                }

                $done_funnels++;
                $imported   = isset( $this->status[ 'imported' ] ) ? (array) $this->status[ 'imported' ] : [];
                $imported[] = [
                    'source_id'    => $data[ 'funnel_id' ],
                    'new_id'       => $result[ 'funnel_id' ],
                    'name'         => get_the_title( $result[ 'funnel_id' ] ),
                    'steps'        => $result[ 'steps' ],
                    'failed_steps' => $result[ 'failed_steps' ],
                    'status'       => $result[ 'failed_steps' ] > 0 ? 'partial' : 'ok',
                    'edit_url'     => add_query_arg(
                        [
                            'page'    => WPFNL_EDIT_FUNNEL_SLUG,
                            'id'      => $result[ 'funnel_id' ],
                            'step_id' => $result[ 'first_step_id' ],
                        ],
                        admin_url( 'admin.php' )
                    ),
                ];

                $this->set_import_status(
                    [
                        'done_funnels' => $done_funnels,
                        'done_steps'   => $done_steps,
                        'imported'     => $imported,
                    ]
                );
            }
        }

        // Flush Elementor's file cache once for the whole import. clear_cache() unlinks the
        // entire Elementor CSS directory and runs three delete_post_meta_by_key() sweeps, so
        // doing it per step multiplied that by the step count.
        if( $this->needs_elementor_cache_flush && class_exists( '\Elementor\Plugin' ) ) {
            Plugin::$instance->files_manager->clear_cache();
            $this->needs_elementor_cache_flush = false;
        }

        $this->finish_import( $run_id, $failed, $this->build_outcome_message( $done_funnels, $failed_funnels ) );
        return true;
    }


    /**
     * Compose the closing message from what actually happened.
     *
     * A single fixed string could not tell "all nine imported" apart from "two imported, seven
     * skipped", which is the case people most need to see.
     *
     * @param int   $imported_count Funnels created.
     * @param array $failures       Collected per-funnel failures.
     *
     * @return string
     * @since 2.5.16
     */
    private function build_outcome_message( $imported_count, $failures ) {
        $failed_count = count( $failures );

        if( 0 === $failed_count ) {
            return __( 'The funnels import process has been successfully completed.', 'wpfnl' );
        }

        if( 0 === $imported_count ) {
            // When every funnel failed for the same reason, that reason is the whole story.
            $reasons = array_unique( wp_list_pluck( $failures, 'message' ) );
            if( 1 === count( $reasons ) ) {
                return reset( $reasons );
            }

            return sprintf(
                /* translators: %d: number of funnels that could not be imported */
                _n( '%d funnel could not be imported.', '%d funnels could not be imported.', $failed_count, 'wpfnl' ),
                $failed_count
            );
        }

        return sprintf(
            /* translators: 1: number imported, 2: total in the file, 3: number that failed */
            __( '%1$d of %2$d funnels imported. %3$d could not be imported.', 'wpfnl' ),
            $imported_count,
            $imported_count + $failed_count,
            $failed_count
        );
    }


    /**
     * Append a per-funnel failure record.
     *
     * @param array  $failures Existing failures.
     * @param array  $data     The funnel payload that failed.
     * @param string $code     Machine-readable reason.
     * @param string $message  Human-readable reason.
     *
     * @return array
     * @since 2.5.16
     */
    private function append_failure( $failures, $data, $code, $message ) {
        $failures[] = [
            'source_id' => isset( $data[ 'funnel_id' ] ) ? $data[ 'funnel_id' ] : 0,
            'name'      => isset( $data[ 'funnel_name' ] ) ? $data[ 'funnel_name' ] : __( 'Untitled funnel', 'wpfnl' ),
            'code'      => $code,
            'message'   => $message,
        ];

        return $failures;
    }


    /**
     * Create one funnel and all of its steps.
     *
     * Extracted from the run loop so a `\Throwable` from any single funnel can be caught and
     * turned into a skip, instead of taking down the whole import.
     *
     * @param array $data       One funnel entry from the payload.
     * @param int   $done_steps Running step counter, updated by reference so progress keeps
     *                          advancing across funnels.
     *
     * @return array|\WP_Error
     * @since 2.5.16
     */
    private function import_single_funnel( $data, &$done_steps ) {
        $funnel       = Wpfnl::$instance->funnel_store;
        $exclude_meta = array( '_is_imported', '_steps_order', '_steps', '_funnel_data' );

        $funnel_data = [];
        $funnel_name = $data[ 'funnel_name' ] ?? 'New Funnel';
        $funnel_id   = $funnel->create( $funnel_name );

        if( !$funnel_id || is_wp_error( $funnel_id ) ) {
            return new \WP_Error( 'funnel_create_failed', __( 'The funnel could not be created.', 'wpfnl' ) );
        }

        $funnel->update_meta( $funnel_id, '_is_imported', 'yes' );

        $_new_step_ids   = [];
        $_new_step_order = [];
        $failed_steps    = 0;

        try {
            foreach( $data[ 'funnel_meta' ] as $meta_key => $meta ) {
                if( !isset( $data[ 'funnel_meta' ][ $meta_key ][ 0 ] ) ) {
                    continue;
                }
                if( in_array( $meta_key, Wpfnl_functions::VOLATILE_TRANSFER_META_KEYS, true ) ) {
                    continue;
                }
                if( !in_array( $meta_key, $exclude_meta ) ) {
                    $funnel->update_meta( $funnel_id, $meta_key, $this->safe_maybe_unserialize( $data[ 'funnel_meta' ][ $meta_key ][ 0 ] ) );
                }
                else {
                    if( 'funnel_data' === $meta_key ) {
                        $funnel_data = $this->safe_maybe_unserialize( $data[ 'funnel_meta' ][ $meta_key ][ 0 ] );
                    }

                    if( '_funnel_data' === $meta_key ) {
                        $funnel_data = $this->safe_maybe_unserialize( $data[ 'funnel_meta' ][ $meta_key ][ 0 ] );
                    }
                }
            }

            $total_steps = count( $data[ 'steps_data' ] );

            foreach( $data[ 'steps_data' ] as $step_data ) {
                $response = $this->import_step( $funnel_id, $step_data );

                if( is_wp_error( $response ) || !isset( $response[ 'id' ], $response[ 'title' ], $response[ 'type' ] ) ) {
                    // Skip the step, keep the funnel: losing one step is better than losing the
                    // funnel, and the result summary reports the funnel as partial.
                    $failed_steps++;
                    continue;
                }

                $_new_step_ids[ $step_data[ 'id' ] ] = $response[ 'id' ];
                $_new_step_order[]                   = [
                    'id'        => $response[ 'id' ],
                    'step_type' => $response[ 'type' ],
                    'name'      => $response[ 'title' ],
                ];

                // Throttled: an unbounded write here would mean one update_option() per step,
                // which on a large funnel is pure DB churn.
                $done_steps++;
                $this->maybe_set_import_status( [ 'done_steps' => $done_steps ] );
            }

            // A funnel whose every step failed is an empty shell. Leaving it behind would put a
            // broken entry in the funnel list that the user then has to find and delete.
            if( $total_steps > 0 && 0 === count( $_new_step_order ) ) {
                $this->rollback_funnel( $funnel_id, [] );

                return new \WP_Error( 'all_steps_failed', __( 'None of the steps in this funnel could be imported.', 'wpfnl' ) );
            }

            $this->update_funnel_data( $_new_step_ids, $funnel_id, $funnel_data );
            update_post_meta( $funnel_id, '_steps_order', $_new_step_order );
            update_post_meta( $funnel_id, '_steps', $_new_step_order );

            $instance = new Wpfnl_functions();
            if( method_exists( $instance, 'generate_first_step' ) ) {
                Wpfnl_functions::generate_first_step( $funnel_id, [] );
            }

            return [
                'funnel_id'     => $funnel_id,
                'steps'         => count( $_new_step_order ),
                'failed_steps'  => $failed_steps,
                'first_step_id' => isset( $_new_step_order[ 0 ][ 'id' ] ) ? $_new_step_order[ 0 ][ 'id' ] : '',
            ];
        }
        catch( \Throwable $e ) {
            // The funnel post already exists at this point, so without this a crash mid-import
            // would leave a half-built funnel in the list. Clean it up, then let the caller
            // record the failure and move on to the next funnel.
            $this->rollback_funnel( $funnel_id, array_values( $_new_step_ids ) );
            throw $e;
        }
    }


    /**
     * Remove a funnel that could not be imported, along with any steps already created for it.
     *
     * @param int   $funnel_id Funnel to delete.
     * @param array $step_ids  Step ids created before the failure.
     *
     * @return void
     * @since 2.5.16
     */
    private function rollback_funnel( $funnel_id, $step_ids ) {
        foreach( (array) $step_ids as $step_id ) {
            if( $step_id ) {
                wp_delete_post( (int) $step_id, true );
            }
        }

        if( $funnel_id ) {
            wp_delete_post( (int) $funnel_id, true );
        }
    }


    /**
     * Write the terminal status record and the fallback admin notice.
     *
     * @param string $run_id  Run id this job was carrying.
     * @param bool   $failed  Whether the run ended on an error.
     * @param string $message Human-readable outcome.
     *
     * @return void
     * @since 2.5.16
     */
    private function finish_import( $run_id, $failed, $message ) {
        if( empty( $this->status ) ) {
            $this->status = self::get_import_status();
        }

        if( $run_id && empty( $this->status[ 'run_id' ] ) ) {
            $this->status[ 'run_id' ] = $run_id;
        }

        $this->set_import_status(
            [
                'state'   => $failed ? 'failed' : 'completed',
                'label'   => $message,
                'message' => $message,
            ]
        );

        // Kept as the "you navigated away" channel: admin_notices is suppressed on the funnels
        // screen itself, so this only ever shows up somewhere else in wp-admin.
        update_option( 'wpfnl_import_notice', $message );
    }
    

    /**
     * Import step from json
     *
     * @param int   $funnel_id
     * @param array $step_data
     *
     * @return array|\WP_Error `[ 'id', 'type', 'title' ]` on success. Returns a WP_Error rather
     *                         than a bare false since 2.5.16, so the caller can report *why* a
     *                         step was skipped in the import summary.
     * @since 1.7.5
     */
    public function import_step($funnel_id, $step_data){
        if (!is_array($step_data)) {
            return new \WP_Error( 'invalid_step_data', __( 'The step data was not readable.', 'wpfnl' ) );
        }
        $builder_type = $this->detect_step_builder($step_data);
        $previous_step_id        = isset($step_data['id']) ? $step_data['id'] : '';
        $title          = isset($step_data['title']) ? $step_data['title'] : '';
        $slug          = isset($step_data['slug']) ? $step_data['slug'] : '';
        $page_template  = isset($step_data['page_template']) ? $step_data['page_template'] : '';
        $post_content   = isset($step_data['post_content']) ? $step_data['post_content'] : '';
        $step_type      = isset($step_data['type']) ? $step_data['type'] : '';
        $step = new Wpfnl_Steps_Store_Data();
        $step_id = $step->create_step($funnel_id, $title, $step_type, $post_content, true);

        if ($step_id) {

            if ($slug) {
                wp_update_post(array(
                    'ID' => $step_id,
                    'post_name' => $slug
                ));
            }

            $step->update_meta($step_id, '_funnel_id', $funnel_id);
            $step->update_meta($step_id, '_wp_page_template', $page_template);
            $exclude_meta = array_merge(
                array('_wpfnl_ab_testing_start_settings', '_funnel_id'),
                Wpfnl_functions::VOLATILE_TRANSFER_META_KEYS
            );
            if ( ! Wpfnl_functions::is_wpfnl_pro_activated() ) {
                $exclude_meta = array_merge( $exclude_meta, array(
                    '_wpfnl_maybe_enable_condition',
                    '_wpfnl_step_conditions',
                    '_wpfnl_next_step_after_condition',
                ) );
            }
            $sql_query_arr      = [];
            $is_enable_condition_meta_blank = false;
            $is_condition_exits = false;
            foreach ($step_data['meta'] as $meta_key => $meta) {

                if (isset($meta[0])) {
                    if (!in_array($meta_key, $exclude_meta)) {
                        if ('_wpfnl_maybe_enable_condition' === $meta_key) {
                            if ('' === $meta[0]) {
                                $is_enable_condition_meta_blank = true;
                            }
                        }

                        if ('_wpfnl_step_conditions' === $meta_key) {
                            if ($meta[0]) {
                                $is_condition_exits = true;
                            }
                        }

                        $sql_data = [
                            'post_id'   => $step_id,
                            'meta_key'  => $meta_key,
                            'meta_value' => $meta[0],
                        ];
                        $sql_query_arr[] = $sql_data;
                    } elseif ('_wpfnl_ab_testing_start_settings' === $meta_key) {

                        $this->update_ab_testing_settings($funnel_id, $step_id, $meta[0], $step_data['ab_step_data']);
                    }
                }
            }
            $this->update_meta_by_sql($sql_query_arr); // update meta

            if ($is_enable_condition_meta_blank && $is_condition_exits) {
                update_post_meta($step_id, '_wpfnl_maybe_enable_condition', 'yes');
            }

            $this->run_builder_post_import($step_id, $builder_type);

            delete_post_meta($step_id, '_funnel_id');
            update_post_meta($step_id, '_funnel_id', $funnel_id);

            return [
                'id'    => $step_id,
                'type'  => $step_type,
                'title' => $title,
            ];
        }

        return new \WP_Error( 'step_create_failed', __( 'The step could not be created.', 'wpfnl' ) );
    }


    /**
     * Update post meta by raw SQL.
     * 
     * Inserts post meta data into the WordPress database using raw SQL queries.
     * 
     * @param array $sql_query_arr An array of SQL query values in the format (post_id, meta_key, meta_value).
     * 
     * @since 1.8.10
     * @return boolean 
     */
    public function update_meta_by_sql( $sql_query_arr ){
        if ( ! is_array( $sql_query_arr ) || empty( $sql_query_arr ) ) {
            return false;
        }
        global $wpdb;
        $insert_sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ";
        $value_placeholders = array();
        $query_args = array();
        foreach( $sql_query_arr as $query ) {
            if( isset($query['post_id'],$query['meta_key'],$query['meta_value']) ){
                $value_placeholders[] = '(%d, %s, %s)';
                $query_args[] = $query['post_id'];
                $query_args[] = $query['meta_key'];
                $query_args[] = $query['meta_value'];
            }
        }
        if( empty( $value_placeholders ) || empty( $query_args )){
            return false;
        }
        $insert_sql_query = rtrim( $insert_sql_query, ',' );
        $insert_sql_query .= ' ' . implode( ',', $value_placeholders );
        $prepared_query = $wpdb->prepare( $insert_sql_query, $query_args );
        $result = $wpdb->query( $prepared_query );
        return false !== $result;
    } 


    /**
     * Update A/B testing settings for a funnel step.
     * This function updates the A/B testing settings for a funnel step with the provided parameters.

     * @param int $funnel_id The ID of the funnel to which the step belongs.
     * @param int $step_id The ID of the step for which the A/B testing settings are being updated.
     * @param mixed $settings The new A/B testing settings for the step. This can be a serialized string or an array.
     * @param mixed $prev_settings The previous A/B testing settings for the step. This can be a serialized string or an array.
     * 
     * @since 1.8.8
     * @return void
    */
    public function update_ab_testing_settings( $funnel_id, $step_id, $settings, $prev_settings ){
        $settings = $this->safe_maybe_unserialize($settings);
        $prev_settings = $this->safe_maybe_unserialize($prev_settings);
        
        if( isset( $settings['variations'] ) ){
            $new_settings = [];
            $new_settings['isStart']  = 'no';
            $new_settings['startDate']  = isset($settings['startDate']) ?$settings['startDate'] : date( 'Y-m-d H:i:s' );
            $new_settings['endDate']  = isset($settings['endDate']) ?$settings['endDate'] : date( 'Y-m-d H:i:s' );
            $new_settings['variations']  = $this->update_ab_variations( $funnel_id, $step_id, $settings['variations'], $prev_settings );
            update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings' , $new_settings );
        }

    }


    /**
     * Update A/B testing variations for a funnel step.
     * This function updates the A/B testing variations for a funnel step with the provided parameters.
     * 
     * @param int $funnel_id The ID of the funnel to which the step belongs.
     * @param int $step_id The ID of the step for which the A/B testing variations are being updated.
     * @param array $variations The new A/B testing variations for the step.
     * @param mixed $prev_settings The previous A/B testing settings for the step.
     * 
     * @return array The updated A/B testing variations.
     * @since 1.8.8
     */
    public function update_ab_variations( $funnel_id, $step_id, $variations, $prev_settings ){
        if( count( $variations ) >= 2 ){
            $step = new Wpfnl_Steps_Store_Data();
            foreach( $variations as $variation_key=>$variation ){
                
                if( !isset($variation['stepId'], $variation['variationType'] ) ){
                    continue;
                }
                
                $key = array_search($variation['stepId'], array_column($prev_settings, 'step_id'));
                if( false !== $key ){
                    
                    if ( 'original' !== $variation['variationType'] && isset($prev_settings[$key]['meta']) ){
                        $metas = $prev_settings[$key]['meta'];
                        $exclude_meta = array_merge(
                            array( '_wpfnl_ab_testing_start_settings','_wpfnl_automation_steps', '_parent_step_id' ),
                            Wpfnl_functions::VOLATILE_TRANSFER_META_KEYS
                        );
                        $page_template  = isset( $metas['page_template'] ) ? $metas['page_template'] : '';
                        $title          = isset( $metas['title'] ) ? $metas['title'] : '';
                        $post_content   = isset( $metas['post_content'] ) ? $metas['post_content'] : '';
                        $step_type      = isset( $metas['type'] ) ? $metas['type'] : '';
                        if( $step ){
                            $new_step_id = $step->create_step($funnel_id, $title, $step_type, $post_content, true);
                            if( $new_step_id ){
                                $step->update_meta($new_step_id, '_funnel_id', $funnel_id);
                                $step->update_meta($new_step_id, '_wp_page_template', $page_template);
                                $sql_query_arr      = [];

                                foreach( $metas['meta'] as $meta_key=>$meta ){
                                    if( !in_array( $meta_key, $exclude_meta ) ){
                                        $sql_data = [
                                            'post_id'   => $new_step_id,
                                            'meta_key'  => $meta_key,
                                            'meta_value'=> $meta[0],
                                        ];
                                        $sql_query_arr[] = $sql_data;
                                    }
                                }
                                $this->update_meta_by_sql( $sql_query_arr ); // update meta
                                // Variants carry their own builder content, so they need the
                                // same post-import pass as a regular step.
                                $this->run_builder_post_import( $new_step_id, $this->detect_step_builder( $metas ) );
                            }
                        }

                    }else{
                        $new_step_id = $step_id;
                    }
                    update_post_meta( $new_step_id, '_parent_step_id', $step_id );
                    $variations[$variation_key]['id'] = $new_step_id;
                    $variations[$variation_key]['stepType'] = get_post_meta( $step_id, '_step_type', true );
                    $step_edit_link =  get_edit_post_link($new_step_id);
                    // `ab_step_data[]` nests the full step payload under its `meta` key
                    // (see Wpfnl_Export::export_ab_step_metas()), so detection reads that.
                    $variation_step_data = isset( $prev_settings[$key]['meta'] ) ? $prev_settings[$key]['meta'] : [];
                    if( 'elementor' === $this->detect_step_builder( $variation_step_data ) ){
                        $step_edit_link = str_replace('&amp;','&',$step_edit_link);
                        $step_edit_link = str_replace('edit','elementor',$step_edit_link);
                    }
                    $variations[$variation_key]['stepEditLink']   		= $step_edit_link;
                    $variations[$variation_key]['stepViewLink']   		= get_the_permalink($new_step_id);
                    $variations[$variation_key]['stepName']       		= get_the_title($new_step_id);
                    $variations[$variation_key]['stepId']       		= $new_step_id;
                }
            }
        }else{
            $default_data = Wpfnl_Ab_Testing::get_default_data( $step_id );
            $variations = $default_data['variations'];
        }
        return $variations;
    }
    


    /**
     * Update funnel data and steps
     * 
     * @param Int $funnel_id
     * @param Array $step_data
     * 
     * @return void 
     * @since  1.7.5
     */ 
    public function update_funnel_data( $new_step_ids, $funnel_id, $funnel_data ){
        $funnel_identifier = array();
		if ($funnel_data) {
			$node_data = isset($funnel_data['drawflow']['Home']['data']) ? $funnel_data['drawflow']['Home']['data'] : [];
			foreach ($node_data as $node_key => $node_value) {
				if(isset($node_value['data']['step_id'])) {
					if ( isset($new_step_ids[$node_value['data']['step_id']]) ) {
						$post_edit_link = base64_encode(get_edit_post_link($new_step_ids[$node_value['data']['step_id']]));
						$post_view_link = base64_encode(get_post_permalink($new_step_ids[$node_value['data']['step_id']]));
						$funnel_data['drawflow']['Home']['data'][$node_key]['data']['step_id'] = $new_step_ids[$node_value['data']['step_id']];
						$funnel_data['drawflow']['Home']['data'][$node_key]['data']['step_edit_link'] = $post_edit_link;
						$funnel_data['drawflow']['Home']['data'][$node_key]['data']['step_view_link'] = $post_view_link;
						$funnel_data['drawflow']['Home']['data'][$node_key]['html'] = $node_value['data']['step_type'] . $new_step_ids[$node_value['data']['step_id']];
						$funnel_identifier[$node_value['id']] = $new_step_ids[$node_value['data']['step_id']];
					} else {
						if ($node_value['data']['step_type'] != 'conditional') {
							$funnel_identifier[$node_value['id']] = $node_value['data']['step_id'];
						} else {
							$funnel_identifier[$node_value['id']] = $node_value['data']['node_identifier'];
						}
					}
				}
                if ($node_value['data']['step_type'] == 'conditional') {
                    $funnel_identifier[$node_value['id']] = $node_value['data']['node_identifier'];
                }
			}
		}
		update_post_meta($funnel_id, '_funnel_data', $funnel_data);
		if ($funnel_identifier) {
			$funnel_identifier_json = json_encode($funnel_identifier, JSON_UNESCAPED_SLASHES);
			update_post_meta($funnel_id, 'funnel_identifier', $funnel_identifier_json);
		}
    }

    /**
     * Hide the import funnel notice based on nonce verification.
     *
     * This method is responsible for hiding the import funnel notice displayed to users. It receives a nonce value from the
     * submitted form, verifies the nonce using the 'wp_verify_nonce' function, and if the verification succeeds, it deletes the
     * 'wpfnl_import_notice' option to hide the notice. It then sends a JSON success response to the client and terminates the script
     * execution using 'wp_die'.
     *
     * @since 1.9.7
     */
    public function hide_import_funnel_notice() {
        $nonce = !empty( $_POST[ 'security' ] ) ? htmlspecialchars( trim( $_POST[ 'security' ] ) ) : null; // phpcs:ignore

        if( wp_verify_nonce( $nonce, 'wpfnl-admin' ) ) {
            delete_option( 'wpfnl_import_notice' );
        }
        wp_send_json_success();
        wp_die();
    }

    public function get_validation_data()
    {
        return [
            'logged_in' => true,
            'user_can' => 'wpf_manage_funnels',
        ];
    }
}