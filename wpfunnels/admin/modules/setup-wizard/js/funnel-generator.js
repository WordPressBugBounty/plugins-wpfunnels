/**
 * Funnel generation core for the setup wizard's store-checkout step.
 *
 * Extracted from the generation logic that used to live duplicated inside
 * StoreCheckout.vue and GenerateFunnel.vue.
 *
 * @since 3.4.0
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Store checkout replaces the native WooCommerce checkout, so only these two
 * step types are ever imported.
 */
const ALLOWED_STEPS = [ 'checkout', 'thankyou' ];

/**
 * Read a step's type regardless of which casing the template API used.
 *
 * @param {Object} step Template step.
 * @return {string} Step type slug, or an empty string.
 */
const stepType = ( step ) => step?.step_type || step?.stepType || step?.type || '';

/**
 * Normalise the REST root so we can safely concatenate a route onto it.
 *
 * @return {string} REST root with exactly one trailing slash.
 */
const restRoot = () => {
	const url = window?.setup_wizard_obj?.rest_api_url || '';
	return url.endsWith( '/' ) ? url : `${ url }/`;
};

/**
 * Reduce a template's steps to just the checkout and thank-you page.
 *
 * @param {Array} steps Template steps.
 * @return {Array} Steps to import.
 */
export function filterStepsForImport( steps ) {
	return ( steps || [] ).filter( ( step ) => ALLOWED_STEPS.includes( stepType( step ) ) );
}

/**
 * Persist the builder picked earlier in the wizard.
 *
 * @param {string} builder Builder slug.
 * @return {Promise} Resolves once settings are saved.
 */
function updateGeneralSettings( builder ) {
	return new Promise( ( resolve, reject ) => {
		wpAjaxHelperRequest( 'update-general-settings', {
			builder: builder || 'gutenberg',
		} )
			.success( () => resolve() )
			.error( ( error ) => reject( error ) );
	} );
}

/**
 * Create the empty funnel that template steps are imported into.
 *
 * Published immediately — a store-checkout funnel takes over the native
 * checkout the moment it exists.
 *
 * @param {Object} template Selected template.
 * @return {Promise<Object>} Funnel creation response.
 */
function createFunnel( template ) {
	const data = {
		steps: filterStepsForImport( template?.steps || [] ),
		name: 'Store Checkout',
		source: 'remote',
		type: 'wc',
		status: 'publish',
		remoteID: template?.ID || template?.id,
		is_store_checkout: true,
	};

	return new Promise( ( resolve, reject ) => {
		wpAjaxHelperRequest( 'wpfunnel-import-funnel', data )
			.success( ( response ) => resolve( response || {} ) )
			.error( ( error ) => reject( error ) );
	} );
}

/**
 * Import each template step into the newly created funnel.
 *
 * Individual step failures are tolerated so one bad step does not abort the
 * whole funnel, but importing zero steps is treated as a hard failure.
 *
 * @param {number} funnelId Funnel ID.
 * @param {Object} template Selected template.
 * @return {Promise<Object>} Imported step IDs keyed by step type, plus the first step link.
 */
async function importSteps( funnelId, template ) {
	const stepsToImport = filterStepsForImport( template?.steps || [] );
	const importedSteps = {};
	let firstStepLink = '';

	for ( let index = 0; index < stepsToImport.length; index++ ) {
		const step = stepsToImport[ index ];

		try {
			const response = await apiFetch( {
				path: `${ restRoot() }wpfunnels/v1/steps/wpfunnel-import-step`,
				method: 'POST',
				data: {
					step,
					funnelID: funnelId,
					source: 'remote',
					importType: 'templates',
				},
			} );

			if ( response?.stepID && step?.step_type ) {
				importedSteps[ step.step_type ] = response.stepID;
			}

			if ( 0 === index && response?.stepViewLink ) {
				firstStepLink = response.stepViewLink;
			}
		} catch ( error ) {
			console.error( `Error importing step ${ stepType( step ) || index }:`, error );
		}
	}

	if ( ! Object.keys( importedSteps ).length ) {
		throw new Error( 'Unable to import the selected template steps.' );
	}

	return { importedSteps, firstStepLink };
}

/**
 * Run WPFunnels' post-creation bookkeeping.
 *
 * Failures here are non-fatal: the funnel and its steps already exist, so a
 * rejected call should not present the user with a failed setup.
 *
 * @param {number} funnelId      Funnel ID.
 * @param {Object} importedSteps Imported step IDs keyed by step type.
 * @return {Promise} Always resolves.
 */
function afterFunnelCreation( funnelId, importedSteps ) {
	return new Promise( ( resolve ) => {
		wpAjaxHelperRequest( 'wpfunnel-after-funnel-creation', {
			funnelID: funnelId,
			source: 'remote',
			importedSteps: Object.values( importedSteps || {} ),
			goal: 'improve-checkout',
			is_store_checkout: true,
		} )
			.success( ( response ) => resolve( response ) )
			.error( () => resolve() );
	} );
}

/**
 * Generate the store-checkout funnel from the selected template.
 *
 * @param {Object}   options          Generation options.
 * @param {string}   options.builder  Selected builder slug.
 * @param {Object}   options.template Selected template.
 * @param {Function} options.onStatus Called with a human-readable progress string.
 * @return {Promise<Object>} The created funnel ID and its first step link.
 */
export async function generateFunnel( { builder, template, onStatus = () => {} } ) {
	if ( ! template || ! Array.isArray( template.steps ) || ! template.steps.length ) {
		throw new Error( 'Unable to generate a funnel because no template was selected.' );
	}

	onStatus( 'Configuring settings...' );
	await updateGeneralSettings( builder );

	onStatus( 'Creating store checkout...' );
	const funnelResponse = await createFunnel( template );
	const funnelId = funnelResponse?.funnelID;

	if ( ! funnelId ) {
		throw new Error( 'Funnel could not be created. Please try again.' );
	}

	onStatus( 'Importing template steps...' );
	const { importedSteps, firstStepLink } = await importSteps( funnelId, template );

	onStatus( 'Finalizing...' );
	await afterFunnelCreation( funnelId, importedSteps );

	return { funnelId, firstStepLink };
}
