<template>
	<button
        class="btn-default"
        id="wpfnl-create-product-btn"
        data-id=""
        @click="showCreateProductModal"
    >
        <span class="create-product-icon">
            <svg width="12" height="12" fill="none" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg"><path stroke="#363B4E" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.918.9v10.013M.9 5.907h10.032"/></svg>
        </span>
        Create New Product
    </button>

    <div class="wpfnl-modal-overlay" v-if="isCreateProduct">
        <div class="wpfnl-modal-wrapper" >
            <div class="wpfnl-modal-close">
                <span class="wpfnl-modal-close-btn" @click="removeCreateProductModal">
                    <svg width="12" height="13" fill="none" viewBox="0 0 12 13" xmlns="http://www.w3.org/2000/svg"><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 1.969l-10 9.99m0-9.99l10 9.99"></path></svg>
                </span>
            </div>
            
            <span className="wpfnl-loader" v-if="isShowModalLoader"></span>
            <div id="wpfnl-create-product" :style="{ display: !isShowModalLoader ? 'block' : 'none' }">
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CreateProduct',
    components: {
        
    },  
    data: function () {
        return {
            isCreateProduct: false,
			isShowModalLoader: false,
            productUrl: window?.setup_wizard_obj?.product_url,
        }
    },
    methods: {
        showCreateProductModal: function () {
			this.isCreateProduct = true;
			this.isShowModalLoader = true;

			setTimeout(() => {
				var container = document.getElementById('wpfnl-create-product');

				// Create an iframe element
				var iframe = document.createElement('iframe');

				// Set the attributes of the iframe
				iframe.setAttribute('src', this.productUrl);
				iframe.setAttribute('width', '100%');
				iframe.setAttribute('height', '100%');
				iframe.setAttribute('frameborder', '0');

				iframe.addEventListener('load', () => {
    				this.isShowModalLoader = false;
				});

				container.appendChild(iframe);
				// Append the iframe to the container div
			}, 100);
		},
		removeCreateProductModal: function () {
			this.isCreateProduct = false
			j('#wpfnl-create-product').empty()
		},
    }
}
</script>