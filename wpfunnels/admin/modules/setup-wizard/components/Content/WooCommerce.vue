<template>
	<div class="wpfnl-mm-setup-section">
        <div class="wpfnl-mm-setup-section-content wpfnl-mm-woo-detection">
            <h3 class="wpfnl-mm-setup-section-title">WooCommerce Status</h3>
            <div class="wpfnl-mm-woo-status">
                <div 
                    class="wpfnl-setup-plugins-card" 
                    :class="{ 'selected': isInstalled }"
                >
                    <span class="wpfnl-setup-plugin-selected" v-if="isInstalled">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M12 5L6.5 10L4 7.72727" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>

                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="24" viewBox="0 0 28 24" fill="none"><path d="M9.93262 0.254883C13.9494 -0.485434 17.9645 1.47377 19.8398 5.0293L20.0146 5.37793C20.1658 5.69992 20.4404 5.93627 20.7783 6.0498L20.7666 6.0625L20.9502 6.09863C24.9773 6.90145 27.9004 10.4697 27.9004 14.582C27.9003 19.3529 24.02 23.2334 19.25 23.2334H6.41699C2.9339 23.2334 0.0998628 20.3986 0.0996094 16.916C0.0996094 15.1403 0.853086 13.436 2.16797 12.2402C2.50685 11.9323 2.6805 11.4972 2.60254 11.0957C2.39141 10.0027 2.37839 8.88008 2.56543 7.75879C3.19687 3.96261 6.15703 0.947348 9.93262 0.254883ZM11.6689 2.22852C11.3347 2.22852 10.9964 2.25068 10.6562 2.29688L10.3154 2.35156C7.46686 2.87377 5.14511 5.23903 4.66797 8.10645C4.52375 8.97664 4.53394 9.84467 4.69727 10.6875C4.91401 11.8061 4.49721 13.0061 3.60449 13.8184C2.73441 14.6097 2.23447 15.739 2.23438 16.9141C2.23438 19.2213 4.11106 21.0986 6.41797 21.0986H19.249V21.0996C22.8426 21.0995 25.7655 18.175 25.7656 14.582C25.7656 11.482 23.5645 8.79519 20.5322 8.18945C19.4451 7.97154 18.5542 7.2814 18.085 6.29297C16.8829 3.75466 14.4044 2.22852 11.6689 2.22852ZM12.835 8.2627C13.4236 8.26294 13.9014 8.74023 13.9014 9.33008V15.4053L16.0635 13.2432C16.4806 12.826 17.1542 12.826 17.5713 13.2432C17.9884 13.6604 17.9884 14.3348 17.5713 14.752L14.4131 17.9102C13.9811 18.3421 13.4132 18.5602 12.8447 18.5625H12.8242C12.2557 18.5602 11.6878 18.3421 11.2559 17.9102L8.09766 14.752C7.68053 14.3348 7.68053 13.6604 8.09766 13.2432C8.51477 12.826 9.18836 12.826 9.60547 13.2432L11.7676 15.4053V9.33008C11.7676 8.74008 12.2461 8.2627 12.835 8.2627Z" fill="#7A8C9A" stroke="white" stroke-width="0.2"/></svg>
                    <span class="wpfnl-setup-plugin-name">
                        {{ isInstalled ? 'Installed' : 'Not Installed' }}
                    </span>
                </div>

                <div 
                    class="wpfnl-setup-plugins-card" 
                    :class="{ 'selected': isActive }"
                >
                    <span class="wpfnl-setup-plugin-selected" v-if="isActive">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M12 5L6.5 10L4 7.72727" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>

                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M13.5238 20H5.52381C3.80952 20 2.85714 20 2.09524 19.6C1.33333 19.2 0.761905 18.6 0.47619 17.9C0 17 0 16 0 14.2V5.8C0 4 8.51495e-08 3 0.380952 2.2C0.761905 1.4 1.33333 0.8 2 0.5C2.85714 0 3.80952 0 5.52381 0H13.3333C13.9048 0 14.2857 0.4 14.2857 1C14.2857 1.6 13.9048 2 13.3333 2H5.52381C4.09524 2 3.33333 2 2.95238 2.2C2.57143 2.4 2.28571 2.7 2.09524 3.1C1.90476 3.5 1.90476 4.3 1.90476 5.8V14.2C1.90476 15.7 1.90476 16.5 2.09524 16.9C2.28571 17.3 2.57143 17.6 2.95238 17.8C3.33333 18 4.09524 18 5.52381 18H13.5238C14.9524 18 15.7143 18 16.0952 17.8C16.4762 17.6 16.7619 17.3 16.9524 16.9C17.1429 16.5 17.1429 15.7 17.1429 14.2V10C17.1429 9.4 17.5238 9 18.0952 9C18.6667 9 19.0476 9.4 19.0476 10V14.2C19.0476 16 19.0476 17 18.6667 17.8C18.2857 18.6 17.7143 19.2 17.0476 19.5C16.1905 20 15.2381 20 13.5238 20ZM9.52381 13C9.2381 13 9.04762 12.9 8.85714 12.7L6 9.7C5.61905 9.3 5.61905 8.7 6 8.3C6.38095 7.9 6.95238 7.9 7.33333 8.3L9.52381 10.6L18.381 1.3C18.7619 0.9 19.3333 0.9 19.7143 1.3C20.0952 1.7 20.0952 2.3 19.7143 2.7L10.1905 12.7C10 12.9 9.80952 13 9.52381 13Z" fill="#7A8C9A"/></svg>
                    <span class="wpfnl-setup-plugin-name">
                        {{ isActive ? 'Activated' : 'Not Activated' }}
                    </span>
                </div>
            </div>

            <div 
                v-if="!isInstalled" 
                class="wpfnl-mm-woo-notice"
            >
                <div class="wpfnl-mm-woo-notice-icon">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM9 5H11V11H9V5ZM10 15C9.45 15 9 14.55 9 14C9 13.45 9.45 13 10 13C10.55 13 11 13.45 11 14C11 14.55 10.55 15 10 15Z" fill="#0073E6"/>
                    </svg>
                </div>
                <div class="wpfnl-mm-woo-notice-content">
                    <h4 class="wpfnl-mm-woo-notice-title">WooCommerce Required</h4>
                    <p class="wpfnl-mm-woo-notice-text">
                        WooCommerce is necessary for creating sales funnels. Don't worry – we'll automatically install and activate it for you during setup.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'WooCommerce',
    props: {
        isInstalled: {
            type: Boolean,
            default: false
        },
        isActive: {
            type: Boolean,
            default: false
        }
    }
}
</script>