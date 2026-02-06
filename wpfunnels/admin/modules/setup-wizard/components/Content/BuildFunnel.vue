<template>
	<div class="wpfnl-mm-build-funnel">
		<!-- Header -->
		<div class="wpfnl-mm-build-funnel-header">
			<h2 class="wpfnl-mm-build-funnel-title">Create Your Revenue-Boosting Funnel</h2>
			<p class="wpfnl-mm-build-funnel-subtitle">
				Choose products and add upsells or order bumps to increase your average order value.
			</p>
		</div>

		<!-- Main Container -->
		<div class="wpfnl-mm-build-funnel-container">
			<!-- Left Panel - Product Selection -->
			<div class="wpfnl-mm-build-funnel-form" v-if="hasCheckout || hasUpsell">
				<div class="wpfnl-mm-build-funnel-fields">
					<!-- Main Product -->
					<div class="wpfnl-mm-build-funnel-field" v-if="hasCheckout">
						<label class="wpfnl-mm-build-funnel-label">Main Product</label>
						<div class="wpfnl-mm-build-funnel-dropdown" :class="{ 'open': showMainProductDropdown, 'has-value': selectedMainProduct }">
							<div class="wpfnl-mm-build-funnel-dropdown-trigger" @click="toggleMainProductDropdown">
								<span v-if="!selectedMainProduct" class="placeholder">Choose product</span>
								<span v-else class="selected-value">
									{{ selectedMainProduct.name }} <span v-html="formatPrice(selectedMainProduct.priceHtml)"></span>
								</span>
								<svg class="dropdown-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M4 6L8 10L12 6" stroke="#7A8C9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</div>
							<div class="wpfnl-mm-build-funnel-dropdown-menu" v-if="showMainProductDropdown" @click.stop>
								<div class="wpfnl-mm-build-funnel-search">
									<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M7.33333 12.6667C10.2789 12.6667 12.6667 10.2789 12.6667 7.33333C12.6667 4.38781 10.2789 2 7.33333 2C4.38781 2 2 4.38781 2 7.33333C2 10.2789 4.38781 12.6667 7.33333 12.6667Z" stroke="#7A8C9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
										<path d="M14 14L11.1 11.1" stroke="#7A8C9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
									<input
										type="text"
										v-model="mainProductSearch"
										@input="searchMainProducts"
										placeholder="Search products..."
										ref="mainProductSearchInput"
									/>
								</div>
								<div class="wpfnl-mm-build-funnel-dropdown-list">
									<div v-if="loadingMainProducts" class="wpfnl-mm-build-funnel-dropdown-loading">
										<svg class="wpfnl-spinner" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
											<circle cx="10" cy="10" r="8" stroke="#6E42D3" stroke-width="2" stroke-dasharray="40 15" stroke-linecap="round"/>
										</svg>
										<span>Searching...</span>
									</div>
									<template v-else>
										<div v-if="mainProducts.length === 0" class="wpfnl-mm-build-funnel-dropdown-empty">
											<p>{{ mainProductSearch ? 'No products found' : 'No products available' }}</p>
											<button class="wpfnl-mm-create-product-btn" @click="showCreateProductModal">
												<svg width="12" height="12" fill="none" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
													<path stroke="#6E42D3" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.918.9v10.013M.9 5.907h10.032"/>
												</svg>
												Create New Product
											</button>
										</div>
										<div
											v-for="product in mainProducts"
											:key="product.id"
											class="wpfnl-mm-build-funnel-dropdown-item"
											:class="{ 'selected': selectedMainProduct && selectedMainProduct.id === product.id }"
											@click="selectMainProduct(product)"
										>
											<span class="product-name">{{ product.name }}</span>
											<span class="product-price" v-html="formatPrice(product.priceHtml)"></span>
											<svg v-if="selectedMainProduct && selectedMainProduct.id === product.id" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M13.3334 4L6.00002 11.3333L2.66669 8" stroke="#6E42D3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											</svg>
										</div>
									</template>
								</div>
							</div>
						</div>
						<p class="wpfnl-mm-build-funnel-hint">Select a product that’s already a top seller to maximize conversions.</p>
					</div>

					<!-- Order Bump -->
					<div class="wpfnl-mm-build-funnel-field" v-if="hasCheckout">
						<label class="wpfnl-mm-build-funnel-label">Order Bump</label>
						<div class="wpfnl-mm-build-funnel-dropdown" :class="{ 'open': showOrderBumpDropdown, 'has-value': selectedOrderBump }">
							<div class="wpfnl-mm-build-funnel-dropdown-trigger" @click="toggleOrderBumpDropdown">
								<span v-if="!selectedOrderBump" class="placeholder">Choose product</span>
								<span v-else class="selected-value">
									{{ selectedOrderBump.name }} <span v-html="formatPrice(selectedOrderBump.priceHtml)"></span>
								</span>
								<svg class="dropdown-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M4 6L8 10L12 6" stroke="#7A8C9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</div>
							<div class="wpfnl-mm-build-funnel-dropdown-menu" v-if="showOrderBumpDropdown" @click.stop>
								<div class="wpfnl-mm-build-funnel-search">
									<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M7.33333 12.6667C10.2789 12.6667 12.6667 10.2789 12.6667 7.33333C12.6667 4.38781 10.2789 2 7.33333 2C4.38781 2 2 4.38781 2 7.33333C2 10.2789 4.38781 12.6667 7.33333 12.6667Z" stroke="#7A8C9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
										<path d="M14 14L11.1 11.1" stroke="#7A8C9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
									<input
										type="text"
										v-model="orderBumpSearch"
										@input="searchOrderBumpProducts"
										placeholder="Search products..."
										ref="orderBumpSearchInput"
									/>
								</div>
								<div class="wpfnl-mm-build-funnel-dropdown-list">
									<div v-if="loadingOrderBumpProducts" class="wpfnl-mm-build-funnel-dropdown-loading">
										<svg class="wpfnl-spinner" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
											<circle cx="10" cy="10" r="8" stroke="#6E42D3" stroke-width="2" stroke-dasharray="40 15" stroke-linecap="round"/>
										</svg>
										<span>Searching...</span>
									</div>
									<template v-else>
										<div v-if="orderBumpProducts.length === 0" class="wpfnl-mm-build-funnel-dropdown-empty">
											<p>{{ orderBumpSearch ? 'No products found' : 'No products available' }}</p>
											<button class="wpfnl-mm-create-product-btn" @click="showCreateProductModal">
												<svg width="12" height="12" fill="none" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
													<path stroke="#6E42D3" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.918.9v10.013M.9 5.907h10.032"/>
												</svg>
												Create New Product
											</button>
										</div>
										<div
											v-for="product in orderBumpProducts"
											:key="product.id"
											class="wpfnl-mm-build-funnel-dropdown-item"
											:class="{ 'selected': selectedOrderBump && selectedOrderBump.id === product.id }"
											@click="selectOrderBump(product)"
										>
											<span class="product-name">{{ product.name }}</span>
											<span class="product-price" v-html="formatPrice(product.priceHtml)"></span>
											<svg v-if="selectedOrderBump && selectedOrderBump.id === product.id" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M13.3334 4L6.00002 11.3333L2.66669 8" stroke="#6E42D3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											</svg>
										</div>
									</template>
								</div>
							</div>
						</div>
						<p class="wpfnl-mm-build-funnel-hint">Add a complementary product with one click at checkout.</p>
					</div>

					<!-- Upsell Product -->
					<div class="wpfnl-mm-build-funnel-field" v-if="hasUpsell">
						<label class="wpfnl-mm-build-funnel-label">Upsell Product <span class="optional">(optional)</span></label>
						<div class="wpfnl-mm-build-funnel-dropdown" :class="{ 'open': showUpsellDropdown, 'has-value': selectedUpsell }">
							<div class="wpfnl-mm-build-funnel-dropdown-trigger" @click="toggleUpsellDropdown">
								<span v-if="!selectedUpsell" class="placeholder">Choose product</span>
								<span v-else class="selected-value">
									{{ selectedUpsell.name }} <span v-html="formatPrice(selectedUpsell.priceHtml)"></span>
								</span>
								<svg class="dropdown-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M4 6L8 10L12 6" stroke="#7A8C9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</div>
							<div class="wpfnl-mm-build-funnel-dropdown-menu" v-if="showUpsellDropdown" @click.stop>
								<div class="wpfnl-mm-build-funnel-search">
									<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M7.33333 12.6667C10.2789 12.6667 12.6667 10.2789 12.6667 7.33333C12.6667 4.38781 10.2789 2 7.33333 2C4.38781 2 2 4.38781 2 7.33333C2 10.2789 4.38781 12.6667 7.33333 12.6667Z" stroke="#7A8C9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
										<path d="M14 14L11.1 11.1" stroke="#7A8C9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
									<input
										type="text"
										v-model="upsellSearch"
										@input="searchUpsellProducts"
										placeholder="Search products..."
										ref="upsellSearchInput"
									/>
								</div>
								<div class="wpfnl-mm-build-funnel-dropdown-list">
									<div v-if="loadingUpsellProducts" class="wpfnl-mm-build-funnel-dropdown-loading">
										<svg class="wpfnl-spinner" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
											<circle cx="10" cy="10" r="8" stroke="#6E42D3" stroke-width="2" stroke-dasharray="40 15" stroke-linecap="round"/>
										</svg>
										<span>Searching...</span>
									</div>
									<template v-else>
										<div v-if="upsellProducts.length === 0" class="wpfnl-mm-build-funnel-dropdown-empty">
											<p>{{ upsellSearch ? 'No products found' : 'No products available' }}</p>
											<button class="wpfnl-mm-create-product-btn" @click="showCreateProductModal">
												<svg width="12" height="12" fill="none" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
													<path stroke="#6E42D3" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.918.9v10.013M.9 5.907h10.032"/>
												</svg>
												Create New Product
											</button>
										</div>
										<div
											v-for="product in upsellProducts"
											:key="product.id"
											class="wpfnl-mm-build-funnel-dropdown-item"
											:class="{ 'selected': selectedUpsell && selectedUpsell.id === product.id }"
											@click="selectUpsell(product)"
										>
											<span class="product-name">{{ product.name }}</span>
											<span class="product-price" v-html="formatPrice(product.priceHtml)"></span>
											<svg v-if="selectedUpsell && selectedUpsell.id === product.id" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M13.3334 4L6.00002 11.3333L2.66669 8" stroke="#6E42D3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											</svg>
										</div>
									</template>
								</div>
							</div>
						</div>
						<p class="wpfnl-mm-build-funnel-hint">Offer an extra product after checkout to increase order value.</p>
					</div>
				</div>

				<!-- Potential Revenue -->
				<div class="wpfnl-mm-build-funnel-revenue" v-if="hasCheckout && selectedMainProduct">
					<div class="wpfnl-mm-revenue-header">
						<p class="wpfnl-mm-build-funnel-revenue-label">Potential Revenue per Customer</p>
					</div>
					
					<div class="wpfnl-mm-revenue-breakdown">
						<!-- Main Product -->
						<div class="wpfnl-mm-revenue-item">
							<div class="wpfnl-mm-revenue-item-content">
								<div class="wpfnl-mm-revenue-item-label">Main Product</div>
								<div class="wpfnl-mm-revenue-item-name">{{ selectedMainProduct.text }}</div>
							</div>
							<div class="wpfnl-mm-revenue-item-price">{{ formatSimplePrice(selectedMainProduct.numericPrice) }}</div>
						</div>
						
						<!-- Order Bump -->
						<div class="wpfnl-mm-revenue-item wpfnl-mm-revenue-item-bonus" v-if="selectedOrderBump">
							<div class="wpfnl-mm-revenue-item-content">
								<div class="wpfnl-mm-revenue-item-label">Order Bump <span class="wpfnl-mm-revenue-badge">+Bonus</span></div>
								<div class="wpfnl-mm-revenue-item-name">{{ selectedOrderBump.text }}</div>
							</div>
							<div class="wpfnl-mm-revenue-item-price wpfnl-mm-revenue-bonus">+{{ formatSimplePrice(selectedOrderBump.numericPrice) }}</div>
						</div>
						
						<!-- Upsell -->
						<div class="wpfnl-mm-revenue-item wpfnl-mm-revenue-item-bonus" v-if="selectedUpsell">
							<div class="wpfnl-mm-revenue-item-content">
								<div class="wpfnl-mm-revenue-item-label">Upsell Product <span class="wpfnl-mm-revenue-badge">+Bonus</span></div>
								<div class="wpfnl-mm-revenue-item-name">{{ selectedUpsell.text }}</div>
							</div>
							<div class="wpfnl-mm-revenue-item-price wpfnl-mm-revenue-bonus">+{{ formatSimplePrice(selectedUpsell.numericPrice) }}</div>
						</div>
						
						<!-- Separator -->
						<div class="wpfnl-mm-revenue-separator"></div>
						
						<!-- Total -->
						<div class="wpfnl-mm-revenue-total">
							<div class="wpfnl-mm-revenue-total-content">
								<div class="wpfnl-mm-revenue-total-label">Total Potential Revenue</div>
								<div class="wpfnl-mm-revenue-total-value">{{ formatSimplePrice(potentialRevenue) }}</div>
							</div>
							<div class="wpfnl-mm-revenue-boost-inline" v-if="selectedOrderBump || selectedUpsell">
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M8 1.33331V14.6666M8 1.33331L3.33334 5.99998M8 1.33331L12.6667 5.99998" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
								<span>+{{ calculateRevenueBoost }}%</span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Right Panel - Preview -->
			<div class="wpfnl-mm-build-funnel-preview">
				<!-- Sub-step Tabs -->
				<div class="wpfnl-mm-build-funnel-tabs">
					<template v-for="(step, index) in availableSteps" :key="step">
						<div
							class="wpfnl-mm-build-funnel-tab"
							:class="{ 'active': activeSubStep === step }"
							@click="setActiveSubStep(step)"
						>
							<div class="wpfnl-mm-build-funnel-tab-icon">
								<svg v-if="step === 'landing'" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
									<rect x="3" y="3" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
									<path d="M3 7H17" stroke="currentColor" stroke-width="1.5"/>
									<path d="M7 7V17" stroke="currentColor" stroke-width="1.5"/>
								</svg>
								<svg v-else-if="step === 'checkout'" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M5 5H17L15.5 13H6.5L5 5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
									<circle cx="7" cy="16" r="1.5" stroke="currentColor" stroke-width="1.5"/>
									<circle cx="15" cy="16" r="1.5" stroke="currentColor" stroke-width="1.5"/>
									<path d="M5 5L4 2H2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
								<svg v-else-if="step === 'upsell'" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M10 4V16M10 4L6 8M10 4L14 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
								<svg v-else-if="step === 'thankyou'" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" stroke="currentColor" stroke-width="1.5"/>
									<path d="M7 10L9 12L13 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</div>
							<span class="wpfnl-mm-build-funnel-tab-label">
								{{ step === 'landing' ? 'Landing' : step === 'checkout' ? 'Checkout' : step === 'upsell' ? 'Upsell' : 'Thank You' }}
							</span>
						</div>
						<div v-if="index < availableSteps.length - 1" class="wpfnl-mm-build-funnel-tab-line"></div>
					</template>
				</div>

				<!-- Preview Content -->
				<div class="wpfnl-mm-build-funnel-preview-content">
					<!-- Landing Preview -->
					<div v-if="activeSubStep === 'landing'" class="wpfnl-mm-build-funnel-preview-image">
						<img v-if="templateImages.landing" :src="templateImages.landing" alt="Landing Page Preview" />
						<div v-else class="wpfnl-mm-build-funnel-preview-placeholder">
							<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
								<rect x="6" y="6" width="36" height="36" rx="4" stroke="#C4C4C4" stroke-width="2"/>
								<path d="M6 14H42" stroke="#C4C4C4" stroke-width="2"/>
								<path d="M14 14V42" stroke="#C4C4C4" stroke-width="2"/>
							</svg>
							<p>Landing page preview</p>
						</div>
					</div>

					<!-- Checkout Preview -->
					<div v-if="activeSubStep === 'checkout'" class="wpfnl-mm-build-funnel-preview-image">
						<img v-if="templateImages.checkout" :src="templateImages.checkout" alt="Checkout Page Preview" />
						<div v-else class="wpfnl-mm-build-funnel-preview-placeholder">
							<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M10 10H42L38 26H14L10 10Z" stroke="#C4C4C4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								<circle cx="16" cy="36" r="3" stroke="#C4C4C4" stroke-width="2"/>
								<circle cx="36" cy="36" r="3" stroke="#C4C4C4" stroke-width="2"/>
							</svg>
							<p>Checkout page preview</p>
						</div>
					</div>

					<!-- Upsell Preview -->
					<div v-if="activeSubStep === 'upsell'" class="wpfnl-mm-build-funnel-preview-image">
						<template v-if="selectedUpsell">
							<img v-if="templateImages.upsell" :src="templateImages.upsell" alt="Upsell Page Preview" />
							<div v-else class="wpfnl-mm-build-funnel-preview-placeholder">
								<p>Upsell page preview</p>
							</div>
						</template>
						<div v-else class="wpfnl-mm-build-funnel-preview-empty">
							<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M24 8V40M24 8L16 16M24 8L32 16" stroke="#C4C4C4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
							<h4>Upsell</h4>
							<p>Select a upsell product to preview your funnel</p>
						</div>
					</div>

					<!-- Thank You Preview -->
					<div v-if="activeSubStep === 'thankyou'" class="wpfnl-mm-build-funnel-preview-image">
						<img v-if="templateImages.thankyou" :src="templateImages.thankyou" alt="Thank You Page Preview" />
						<div v-else class="wpfnl-mm-build-funnel-preview-placeholder">
							<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
								<circle cx="24" cy="24" r="18" stroke="#C4C4C4" stroke-width="2"/>
								<path d="M16 24L22 30L32 18" stroke="#C4C4C4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
							<p>Thank you page preview</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Buttons -->
		<div class="wpfnl-mm-build-funnel-buttons wpfnl-mm-buttons-container">
			<button class="wpfnl-mm-btn wpfnl-mm-btn-secondary" @click="goBack" :disabled="isProcessing">
				Back
			</button>
			<div class="wpfnl-mm-btn-group">
				<button
					class="wpfnl-mm-btn wpfnl-mm-btn-primary"
					@click="handleContinue"
					:disabled="isProcessing"
				>
					<span v-if="!isProcessing">Continue</span>
					<span v-else>Processing...</span>
					<svg v-if="!isProcessing" width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M1 6H16M16 6L11 1M16 6L11 11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<svg v-else class="wpfnl-spinner" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<circle cx="8" cy="8" r="6" stroke="white" stroke-width="2" stroke-dasharray="30 10" stroke-linecap="round"/>
					</svg>
				</button>
			</div>
		</div>

		<!-- Create Product Modal -->
		<div class="wpfnl-modal-overlay" v-if="isCreateProduct">
			<div class="wpfnl-modal-wrapper">
				<div class="wpfnl-modal-close">
					<span class="wpfnl-modal-close-btn" @click="removeCreateProductModal">
						<svg width="12" height="13" fill="none" viewBox="0 0 12 13" xmlns="http://www.w3.org/2000/svg">
							<path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 1.969l-10 9.99m0-9.99l10 9.99"></path>
						</svg>
					</span>
				</div>
				
				<span class="wpfnl-loader" v-if="isShowModalLoader"></span>
				<div id="wpfnl-create-product" :style="{ display: !isShowModalLoader ? 'block' : 'none' }">
				</div>
			</div>
		</div>

		<!-- Help Button -->
		<div class="wpfnl-mm-help-btn">
			<button class="wpfnl-mm-help-btn-circle" @click="showHelp">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M6.06 5.99999C6.2167 5.55443 6.52627 5.17872 6.93329 4.93941C7.34031 4.7001 7.81926 4.61252 8.28478 4.69247C8.75031 4.77242 9.17254 5.01433 9.47671 5.37567C9.78087 5.73702 9.94736 6.19433 9.94667 6.66666C9.94667 7.99999 7.94667 8.66666 7.94667 8.66666M8 11.3333H8.00667M14.6667 8C14.6667 11.6819 11.6819 14.6667 8 14.6667C4.31811 14.6667 1.33334 11.6819 1.33334 8C1.33334 4.31809 4.31811 1.33333 8 1.33333C11.6819 1.33333 14.6667 4.31809 14.6667 8Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		</div>
	</div>
</template>

<script>
import apiFetch from '@wordpress/api-fetch'
import { addQueryArgs } from '@wordpress/url'
import { __ } from '@wordpress/i18n'

export default {
	name: 'BuildFunnel',
	props: {
		template: {
			type: Object,
			default: null
		},
		goal: {
			type: String,
			default: 'sales'
		},
		builder: {
			type: String,
			default: 'gutenberg'
		}
	},
	data() {
		return {
			activeSubStep: 'landing',
			isProcessing: false,
			isCreateProduct: false,
			isShowModalLoader: false,

			// Dropdown states
			showMainProductDropdown: false,
			showOrderBumpDropdown: false,
			showUpsellDropdown: false,

			// Search queries
			mainProductSearch: '',
			orderBumpSearch: '',
			upsellSearch: '',

			// Loading states
			loadingMainProducts: false,
			loadingOrderBumpProducts: false,
			loadingUpsellProducts: false,

			// Search results
			mainProducts: [],
			orderBumpProducts: [],
			upsellProducts: [],

			// Selected products
			selectedMainProduct: null,
			selectedOrderBump: null,
			selectedUpsell: null,

			// Debounce timers
			mainProductDebounce: null,
			orderBumpDebounce: null,
			upsellDebounce: null,

			// Template images
			templateImages: {
				landing: '',
				checkout: '',
				upsell: '',
				thankyou: ''
			}
		}
	},
	computed: {
		potentialRevenue() {
			let total = 0
			if (this.selectedMainProduct) {
				total += parseFloat(this.selectedMainProduct.numericPrice) || 0
			}
			if (this.selectedOrderBump) {
				total += parseFloat(this.selectedOrderBump.numericPrice) || 0
			}
			if (this.selectedUpsell) {
				total += parseFloat(this.selectedUpsell.numericPrice) || 0
			}
			return total
		},
		calculateRevenueBoost() {
			if (!this.selectedMainProduct) return 0
			
			const mainPrice = this.selectedMainProduct.numericPrice || 0
			const bumpPrice = this.selectedOrderBump ? (this.selectedOrderBump.numericPrice || 0) : 0
			const upsellPrice = this.selectedUpsell ? (this.selectedUpsell.numericPrice || 0) : 0
			
			if (mainPrice === 0) return 0
			
			const additionalRevenue = bumpPrice + upsellPrice
			const boost = (additionalRevenue / mainPrice) * 100
			return Math.round(boost)
		},
		hasLanding() {
			if (!this.template || !this.template.steps) return false
			return this.template.steps.some(step => step.step_type === 'landing')
		},
		hasCheckout() {
			if (!this.template || !this.template.steps) return false
			return this.template.steps.some(step => step.step_type === 'checkout')
		},
		hasUpsell() {
			if (!this.template || !this.template.steps) return false
			return this.template.steps.some(step => step.step_type === 'upsell')
		},
		hasThankyou() {
			if (!this.template || !this.template.steps) return false
			return this.template.steps.some(step => step.step_type === 'thankyou')
		},
		availableSteps() {
			const steps = []
			if (this.hasLanding) steps.push('landing')
			if (this.hasCheckout) steps.push('checkout')
			if (this.hasUpsell) steps.push('upsell')
			if (this.hasThankyou) steps.push('thankyou')
			return steps
		}
	},
	mounted() {
		this.loadTemplateImages()
		document.addEventListener('click', this.closeDropdowns)
		// Set active tab to first available step
		if (this.availableSteps.length > 0) {
			this.activeSubStep = this.availableSteps[0]
		}
	},
	beforeDestroy() {
		document.removeEventListener('click', this.closeDropdowns)
		// Clear debounce timers
		if (this.mainProductDebounce) clearTimeout(this.mainProductDebounce)
		if (this.orderBumpDebounce) clearTimeout(this.orderBumpDebounce)
		if (this.upsellDebounce) clearTimeout(this.upsellDebounce)
	},
	methods: {
		stripHtml(html) {
			if (!html) return ''
			const tmp = document.createElement('DIV')
			tmp.innerHTML = html
			return tmp.textContent || tmp.innerText || ''
		},

		extractNumericPrice(priceString) {
			if (!priceString) return 0
			
			// If it's already a number, return it
			if (typeof priceString === 'number') {
				return priceString
			}
			
			// Convert to string if not already
			const priceStr = String(priceString)
			
			// Strip HTML tags first
			const cleanPrice = this.stripHtml(priceStr)
			
			// Remove all non-numeric characters except dots and commas
			// Then handle different decimal separator formats
			let numericStr = cleanPrice.replace(/[^\d.,]/g, '')
			
			// Handle European format (1.234,56) vs US format (1,234.56)
			// If there's both comma and dot, determine which is the decimal separator
			if (numericStr.includes(',') && numericStr.includes('.')) {
				// If comma comes after dot, comma is decimal separator (European)
				if (numericStr.lastIndexOf(',') > numericStr.lastIndexOf('.')) {
					numericStr = numericStr.replace(/\./g, '').replace(',', '.')
				} else {
					// US format - just remove commas
					numericStr = numericStr.replace(/,/g, '')
				}
			} else if (numericStr.includes(',')) {
				// Only comma - could be decimal or thousand separator
				// If there are 2 digits after the last comma, it's a decimal separator
				const parts = numericStr.split(',')
				if (parts.length === 2 && parts[1].length <= 2) {
					numericStr = numericStr.replace(',', '.')
				} else {
					numericStr = numericStr.replace(/,/g, '')
				}
			}
			
			const result = parseFloat(numericStr)
			return isNaN(result) ? 0 : result
		},

		formatPrice(priceString) {
			if (!priceString) return this.getCurrencySymbol() + '0.00'
			// If it contains HTML, return as is for v-html
			if (priceString.includes('<')) {
				return priceString
			}
			// Otherwise format as simple price with currency
			const symbol = this.getCurrencySymbol()
			const position = window.setup_wizard_obj?.currency_position || 'left'
			
			if (position === 'right') {
				return `${priceString}${symbol}`
			} else if (position === 'right_space') {
				return `${priceString} ${symbol}`
			} else if (position === 'left_space') {
				return `${symbol} ${priceString}`
			}
			// Default: left
			return `${symbol}${priceString}`
		},

		getCurrencySymbol() {
			return window.setup_wizard_obj?.currency_symbol || '$'
		},

		formatSimplePrice(amount) {
			const symbol = this.getCurrencySymbol()
			const position = window.setup_wizard_obj?.currency_position || 'left'
			
			// Ensure amount is a valid number
			let numericAmount = 0
			if (typeof amount === 'number') {
				numericAmount = amount
			} else if (typeof amount === 'string') {
				numericAmount = parseFloat(amount) || 0
			}
			
			const price = numericAmount.toFixed(2)
			
			if (position === 'right') {
				return `${price}${symbol}`
			} else if (position === 'right_space') {
				return `${price} ${symbol}`
			} else if (position === 'left_space') {
				return `${symbol} ${price}`
			}
			// Default: left
			return `${symbol}${price}`
		},

		async searchProducts(query, limit = 0) {
			try {
				const params = new URLSearchParams({
					action: 'wpfnl_product_search',
					term: query || '',
					security: window.setup_wizard_obj.admin_nonce,
					isLms: 'false'
				})

				if (limit > 0) {
					params.append('limit', limit)
				}

				const response = await fetch(`${window.setup_wizard_obj.ajax_url}?${params.toString()}`, {
					method: 'GET'
				})

				const data = await response.json()
				
				if (data && typeof data === 'object') {
					// Convert object to array format
					return Object.keys(data).map(id => {
						const product = data[id]
						// Try to get price from various possible fields
						const priceValue = product.price || product.regular_price || product.sale_price || '0'
						
						return {
							id: id,
							name: product.name || product.text || '',
							price: priceValue,
							priceHtml: priceValue,
							numericPrice: this.extractNumericPrice(priceValue)
						}
					})
				}
				return []
			} catch (error) {
				console.error('Error searching products:', error)
				return []
			}
		},

		searchMainProducts() {
			if (this.mainProductDebounce) clearTimeout(this.mainProductDebounce)

			this.mainProductDebounce = setTimeout(async () => {
				if (!this.mainProductSearch || this.mainProductSearch.length < 2) {
					return
				}

				this.loadingMainProducts = true
				this.mainProducts = await this.searchProducts(this.mainProductSearch)
				this.loadingMainProducts = false
			}, 300)
		},

		async loadInitialProducts(type) {
			if (type === 'main') {
				if (this.mainProducts.length > 0) {
					return
				}
				this.loadingMainProducts = true
				// Use a single space or wildcard to get products
				const products = await this.searchProducts(' ', 10)
				this.mainProducts = products
				this.loadingMainProducts = false
			} else if (type === 'orderbump') {
				if (this.orderBumpProducts.length > 0) {
					return
				}
				this.loadingOrderBumpProducts = true
				const products = await this.searchProducts(' ', 10)
				this.orderBumpProducts = products
				this.loadingOrderBumpProducts = false
			} else if (type === 'upsell') {
				if (this.upsellProducts.length > 0) {
					return
				}
				this.loadingUpsellProducts = true
				const products = await this.searchProducts(' ', 10)
				this.upsellProducts = products
				this.loadingUpsellProducts = false
			}
		},

		searchOrderBumpProducts() {
			if (this.orderBumpDebounce) clearTimeout(this.orderBumpDebounce)

			this.orderBumpDebounce = setTimeout(async () => {
				if (!this.orderBumpSearch || this.orderBumpSearch.length < 2) {
					return
				}

				this.loadingOrderBumpProducts = true
				this.orderBumpProducts = await this.searchProducts(this.orderBumpSearch)
				this.loadingOrderBumpProducts = false
			}, 300)
		},

		searchUpsellProducts() {
			if (this.upsellDebounce) clearTimeout(this.upsellDebounce)

			this.upsellDebounce = setTimeout(async () => {
				if (!this.upsellSearch || this.upsellSearch.length < 2) {
					return
				}

				this.loadingUpsellProducts = true
				this.upsellProducts = await this.searchProducts(this.upsellSearch)
				this.loadingUpsellProducts = false
			}, 300)
		},

		loadTemplateImages() {
			if (this.template && this.template.steps) {
				this.template.steps.forEach(step => {
					if (step.step_type === 'landing') {
						this.templateImages.landing = step.featured_image || step.preview_image || ''
					} else if (step.step_type === 'checkout') {
						this.templateImages.checkout = step.featured_image || step.preview_image || ''
					} else if (step.step_type === 'upsell') {
						this.templateImages.upsell = step.featured_image || step.preview_image || ''
					} else if (step.step_type === 'thankyou') {
						this.templateImages.thankyou = step.featured_image || step.preview_image || ''
					}
				})
			}

			// Use template featured image as fallback for landing
			if (!this.templateImages.landing && this.template && this.template.featured_image) {
				this.templateImages.landing = this.template.featured_image
			}
		},

		closeDropdowns(e) {
			if (!e.target.closest('.wpfnl-mm-build-funnel-dropdown')) {
				this.showMainProductDropdown = false
				this.showOrderBumpDropdown = false
				this.showUpsellDropdown = false
			}
		},

		toggleMainProductDropdown() {
			this.showMainProductDropdown = !this.showMainProductDropdown
			this.showOrderBumpDropdown = false
			this.showUpsellDropdown = false

			if (this.showMainProductDropdown) {
				this.loadInitialProducts('main')
				this.$nextTick(() => {
					if (this.$refs.mainProductSearchInput) {
						this.$refs.mainProductSearchInput.focus()
					}
				})
			}
		},

		toggleOrderBumpDropdown() {
			this.showOrderBumpDropdown = !this.showOrderBumpDropdown
			this.showMainProductDropdown = false
			this.showUpsellDropdown = false

			if (this.showOrderBumpDropdown) {
				this.loadInitialProducts('orderbump')
				this.$nextTick(() => {
					if (this.$refs.orderBumpSearchInput) {
						this.$refs.orderBumpSearchInput.focus()
					}
				})
			}
		},

		toggleUpsellDropdown() {
			this.showUpsellDropdown = !this.showUpsellDropdown
			this.showMainProductDropdown = false
			this.showOrderBumpDropdown = false

			if (this.showUpsellDropdown) {
				this.loadInitialProducts('upsell')
				this.$nextTick(() => {
					if (this.$refs.upsellSearchInput) {
						this.$refs.upsellSearchInput.focus()
					}
				})
			}
		},

		selectMainProduct(product) {
			this.selectedMainProduct = product
			this.showMainProductDropdown = false
			this.mainProductSearch = ''
			this.mainProducts = []
			// Auto-switch to checkout preview if available
			if (this.hasCheckout) {
				this.activeSubStep = 'checkout'
			}
		},

		selectOrderBump(product) {
			this.selectedOrderBump = product
			this.showOrderBumpDropdown = false
			this.orderBumpSearch = ''
			this.orderBumpProducts = []
			// Auto-switch to checkout preview if available
			if (this.hasCheckout) {
				this.activeSubStep = 'checkout'
			}
		},

		selectUpsell(product) {
			this.selectedUpsell = product
			this.showUpsellDropdown = false
			this.upsellSearch = ''
			this.upsellProducts = []
			// Auto-switch to upsell preview if available
			if (this.hasUpsell) {
				this.activeSubStep = 'upsell'
			}
		},

		setActiveSubStep(step) {
			this.activeSubStep = step
		},

		showCreateProductModal() {
			this.isCreateProduct = true
			this.isShowModalLoader = true

			setTimeout(() => {
				const container = document.getElementById('wpfnl-create-product')
				const productUrl = window?.setup_wizard_obj?.product_url

				// Create an iframe element
				const iframe = document.createElement('iframe')

				// Set the attributes of the iframe
				iframe.setAttribute('src', productUrl)
				iframe.setAttribute('width', '100%')
				iframe.setAttribute('height', '100%')
				iframe.setAttribute('frameborder', '0')

				iframe.addEventListener('load', () => {
					this.isShowModalLoader = false
				})

				container.appendChild(iframe)
			}, 100)
		},

		removeCreateProductModal() {
			this.isCreateProduct = false
			const container = document.getElementById('wpfnl-create-product')
			if (container) {
				container.innerHTML = ''
			}
		},

		goBack() {
			this.$emit('prev-step')
		},

		async assignProductsToSteps(importedSteps) {

			// Assign main product to checkout step
			if (importedSteps.checkout && this.selectedMainProduct) {
				try {
					await apiFetch({
						path: `${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/checkout/wpfnl-add-product`,
						method: 'POST',
						data: {
							id: this.selectedMainProduct.id,
							step_id: importedSteps.checkout,
							quantity: 1
						}
					})
				} catch (error) {
					console.error('Error assigning main product:', error)
				}
			}

			// Assign order bump to checkout step using the proper endpoint with full structure
			if (importedSteps.checkout && this.selectedOrderBump) {
				try {
					// Get product image from WPFunnels API
					let productImage = {
						id: 0,
						url: window.setup_wizard_obj.plugin_url ? window.setup_wizard_obj.plugin_url + 'admin/assets/images/placeholder.png' : ''
					}

					// Fetch product details to get the image
					try {
						const productData = await apiFetch({
							path: `${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/updateSelectedProduct?product=${this.selectedOrderBump.id}`,
							method: 'GET'
						})

						if (productData && productData.img) {
							// productData.img is an HTML img tag, we need to parse it
							const imgTag = productData.img
							
							// Extract src URL from img tag
							const srcMatch = imgTag.match(/src="([^"]+)"/)
							if (srcMatch) {
								productImage.url = srcMatch[1]
							}
							
							// Extract image ID from wp-image-* class if available
							const idMatch = imgTag.match(/wp-image-(\d+)/)
							if (idMatch) {
								productImage.id = parseInt(idMatch[1])
							}
						}
					} catch (imgError) {
						console.error('Could not fetch product image, using placeholder:', imgError)
					}

					const orderBumpData = [{
						name: 'Order Bump',
						selectedStyle: 'style1',
						isEnabled: true,
						position: 'after-order',
						product: this.selectedOrderBump.id,
						productName: this.selectedOrderBump.name,
						chooseVariantName: 'Choose an Option',
						productSearchName: this.selectedOrderBump.name,
						productType: '',
						price: '',
						salePrice: '',
						quantity: '1',
						htmlPrice: this.selectedOrderBump.priceHtml,
						numericRegularPrice: '',
						numericSalePrice: '',
						discountPrice: '',
						discountPriceHtml: '',
						productImage: productImage,
						highLightText: this.selectedOrderBump.name,
						checkBoxLabel: 'Yes, I want this!',
						productDescriptionText: 'Add this special offer to your order',
						discountOption: 'original',
						discountapply: 'regular',
						discountValue: '',
						couponName: '',
						obNextStep: 'default',
						isReplace: 'no',
						replaceSettings: {
							isAllReplace: 'yes',
							selectedProducts: []
						},
						replace: '',
						obArrowColor: '#EE8134',
						obPrimaryColor: '#6E42D2',
						obBgColor: '',
						obTitleColor: '#363B4E',
						obHighlightColor: '#6E42D3',
						obCheckboxTitleColor: '#d9d9d9',
						obDescriptionColor: '#7A8B9A',
						isRemoveObBgColor: '',
						obPriceColor: '#E86F2C',
						obChooseVariantColor: '#F34D01',
						prePurchaseUpsell: 'no'
					}]
					
					await apiFetch({
						path: `${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/order-bump`,
						method: 'POST',
						data: {
							value: orderBumpData,
							stepID: importedSteps.checkout
						}
					})
				} catch (error) {
					console.error('Error creating order bump:', error)
				}
			}

			// Assign upsell product to upsell step - correct format with id and quantity
			if (importedSteps.upsell && this.selectedUpsell) {
				try {
					const productData = {
						id: this.selectedUpsell.id,
						quantity: 1
					}
					
					await apiFetch({
						path: `${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/offer/saveUpsellData/`,
						method: 'POST',
						data: {
							step_id: importedSteps.upsell,
							product: JSON.stringify(productData)
						}
					})
				} catch (error) {
					console.error('Error assigning upsell product:', error)
				}
			}
		},

		async handleContinue() {
			this.isProcessing = true

			try {
				if (!this.template) {
					console.error('No template selected')
					this.isProcessing = false
					return
				}

				// Step 1: Map goal to funnel type
				const funnelType = this.goal === 'leads' ? 'lead' : 'sales'
				
				// Step 2: Determine template type based on goal
				let templateType = 'lead'
				if (funnelType === 'sales') {
					// Check if WooCommerce is active for wc type
					templateType = window.setup_wizard_obj?.is_woo_active === 'yes' ? 'wc' : 'lead'
				}

				// Step 3: Update general settings (funnel type and builder)
				await new Promise((resolve, reject) => {
					wpAjaxHelperRequest('update-general-settings', {
						funnel_type: funnelType,
						builder: this.builder
					})
					.success(() => resolve())
					.error((error) => reject(error))
				})

				// Step 4: Create funnel using wpfunnel-import-funnel
				const funnelResponse = await new Promise((resolve, reject) => {
					wpAjaxHelperRequest('wpfunnel-import-funnel', {
						steps: this.template.steps || [],
						name: this.template.title || 'My First Funnel',
						source: 'remote',
						remoteID: this.template.ID || this.template.id,
						type: templateType,
						status: 'draft'
					})
					.success((response) => resolve(response))
					.error((error) => reject(error))
				})

				if (!funnelResponse.funnelID) {
					throw new Error('Failed to create funnel')
				}

				const funnelID = funnelResponse.funnelID

			// Step 5: Import each step and track step IDs
			const steps = this.template.steps || []
			
			// Check if pro is active
			const isProActive = window.setup_wizard_obj?.is_pro_active === 'yes'

			
			// Filter out downsell steps if pro is not active (downsell is a Pro feature)
			const stepsToImport = steps.filter(step => {
				// Check multiple possible field names for step type
				const stepType = step.step_type || step.stepType || step.type
				
				// Skip downsell if pro is not active
				if (stepType === 'downsell' && !isProActive) {
					return false
				}
				
				return true
			})
			
			
			let stepCount = 0
			const importedSteps = {}  // Store step IDs by step type
			let firstStepLink = ''  // Store the first step's view link

			for (let i = 0; i < stepsToImport.length; i++) {
				const step = stepsToImport[i]
				
				try {
					const stepResponse = await apiFetch({
						path: `${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/steps/wpfunnel-import-step`,
						method: 'POST',
						data: {
							step: step,
							funnelID: funnelID,
							source: 'remote',
							importType: 'templates'
						}
					})
					
					// Store step ID by type
					if (stepResponse.stepID && step.step_type) {
						importedSteps[step.step_type] = stepResponse.stepID
					}
					
					// Store the first step's view link
					if (i === 0 && stepResponse.stepViewLink) {
						firstStepLink = stepResponse.stepViewLink
					}
					
					stepCount++

				} catch (error) {
					console.error(`Error importing step ${i + 1}:`, error)
				}
				}

			// Step 6: Assign products to steps
			await this.assignProductsToSteps(importedSteps)

			// Step 7: Call after-funnel-creation
				await new Promise((resolve, reject) => {
					wpAjaxHelperRequest('wpfunnel-after-funnel-creation', {
						funnelID: funnelID,
						source: 'remote'
					})
					.success((response) => {
						resolve(response)
					})
					.error((error) => {
						console.error('After funnel creation error:', error)
						resolve()
					})
				})

				// Success! Emit next-step event with funnelId and firstStepLink to go to Complete step
				this.isProcessing = false
				this.$emit('next-step', { funnelId: funnelID, firstStepLink: firstStepLink })

			} catch (error) {
				console.error('Error creating funnel:', error)
				alert('An error occurred while creating the funnel. Please try again.')
				this.isProcessing = false
			}
		},
	}
}
</script>

<style scoped>
.wpfnl-mm-build-funnel {
	width: 100%;
	max-width: 1049px;
	display: flex;
	flex-direction: column;
	gap: 15px;
	position: relative;
}

/* Header */
.wpfnl-mm-build-funnel-header {
	text-align: center;
	margin-bottom: 5px;
}

.wpfnl-mm-build-funnel-title {
	font-size: 24px;
	font-weight: 700;
	font-family: 'DM Sans', sans-serif;
	color: #363B4E;
	line-height: 35px;
	letter-spacing: -1px;
	margin: 0 0 8px 0;
}

.wpfnl-mm-build-funnel-subtitle {
	font-size: 15px;
	font-weight: 400;
	font-family: 'DM Sans', sans-serif;
	color: #6E7A85;
	line-height: 15px;
	margin: 0;
}

/* Main Container */
.wpfnl-mm-build-funnel-container {
	display: flex;
	gap: 3px;
}

/* Left Panel - Form */
.wpfnl-mm-build-funnel-form {
	width: 461px;
	background: #FFF;
	border-radius: 16px;
	padding: 40px;
	display: flex;
	flex-direction: column;
	min-height: 556px;
}

.wpfnl-mm-build-funnel-fields {
	display: flex;
	flex-direction: column;
	gap: 40px;
	flex: 1;
}

.wpfnl-mm-build-funnel-field {
	display: flex;
	flex-direction: column;
	gap: 9px;
}

.wpfnl-mm-build-funnel-label {
	font-size: 15px;
	font-weight: 500;
	font-family: 'DM Sans', sans-serif;
	color: #363B4E;
	letter-spacing: -0.44px;
	line-height: 16px;
}

.wpfnl-mm-build-funnel-label .required {
	color: red;
}

.wpfnl-mm-build-funnel-label .optional {
	font-weight: 400;
	color: #7A8C9A;
}

/* Dropdown */
.wpfnl-mm-build-funnel-dropdown {
	position: relative;
}

.wpfnl-mm-build-funnel-dropdown-trigger {
	height: 46px;
	background: #FFF;
	border: 1px solid #F1EFF7;
	border-radius: 8px;
	padding: 0 16px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	cursor: pointer;
	transition: all 0.3s ease;
}

.wpfnl-mm-build-funnel-dropdown.open .wpfnl-mm-build-funnel-dropdown-trigger {
	border-color: #6E42D3;
}

.wpfnl-mm-build-funnel-dropdown-trigger .placeholder {
	font-size: 14px;
	font-family: 'DM Sans', sans-serif;
	color: #7A8C9A;
}

.wpfnl-mm-build-funnel-dropdown-trigger .selected-value {
	font-size: 14px;
	font-family: 'DM Sans', sans-serif;
	color: #363B4E;
}

.wpfnl-mm-build-funnel-dropdown-trigger .dropdown-icon {
	transition: transform 0.3s ease;
}

.wpfnl-mm-build-funnel-dropdown.open .dropdown-icon {
	transform: rotate(180deg);
}

.wpfnl-mm-build-funnel-dropdown-menu {
	position: absolute;
	top: 100%;
	left: 0;
	right: 0;
	background: #FFF;
	border: 1px solid #F1EFF7;
	border-radius: 8px;
	margin-top: 4px;
	z-index: 100;
	box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
	overflow: hidden;
}

/* Search Input */
.wpfnl-mm-build-funnel-search {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 12px 16px;
	border-bottom: 1px solid #F1EFF7;
}

.wpfnl-mm-build-funnel-search svg {
	flex-shrink: 0;
}

.wpfnl-mm-build-funnel-search input {
	flex: 1;
	border: none;
	outline: none;
	font-size: 14px;
	font-family: 'DM Sans', sans-serif;
	color: #363B4E;
	background: transparent;
}

.wpfnl-mm-build-funnel-search input::placeholder {
	color: #7A8C9A;
}

.wpfnl-mm-build-funnel-dropdown-list {
	max-height: 200px;
	overflow-y: auto;
}

.wpfnl-mm-build-funnel-dropdown-loading,
.wpfnl-mm-build-funnel-dropdown-empty {
	padding: 16px;
	text-align: center;
	font-size: 14px;
	font-family: 'DM Sans', sans-serif;
	color: #7A8C9A;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 12px;
}

.wpfnl-mm-build-funnel-dropdown-empty p {
	margin: 0;
}

.wpfnl-mm-create-product-btn {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	padding: 8px 16px;
	background: #FFF;
	border: 1px solid #6E42D3;
	border-radius: 6px;
	font-size: 13px;
	font-weight: 500;
	font-family: 'DM Sans', sans-serif;
	color: #6E42D3;
	cursor: pointer;
	transition: all 0.3s ease;
}

.wpfnl-mm-create-product-btn:hover {
	background: #6E42D3;
	color: #FFF;
}

.wpfnl-mm-create-product-btn:hover svg path {
	stroke: #FFF;
}

.wpfnl-mm-create-product-btn svg {
	width: 12px;
	height: 12px;
}

.wpfnl-mm-build-funnel-dropdown-item {
	padding: 12px 16px;
	font-size: 14px;
	font-family: 'DM Sans', sans-serif;
	color: #363B4E;
	cursor: pointer;
	display: flex;
	align-items: center;
	gap: 8px;
	transition: background 0.2s ease;
}

.wpfnl-mm-build-funnel-dropdown-item:hover {
	background: #F6F5FA;
}

.wpfnl-mm-build-funnel-dropdown-item.selected {
	background: #F6F5FA;
	color: #6E42D3;
}

.wpfnl-mm-build-funnel-dropdown-item .product-name {
	flex: 1;
}

.wpfnl-mm-build-funnel-dropdown-item .product-price {
	color: #6E42D3;
	font-weight: 500;
}

.wpfnl-mm-build-funnel-hint {
	font-size: 12px;
	font-family: 'Inter', sans-serif;
	color: #7A8C9A;
	line-height: 16px;
	margin: 0;
}

/* Potential Revenue */
.wpfnl-mm-build-funnel-revenue {
	margin-top: auto;
	padding: 20px;
	background: #FFFFFF;
	border: 1px solid #ECEBF0;
	border-radius: 12px;
}

.wpfnl-mm-revenue-header {
	margin-bottom: 16px;
}

.wpfnl-mm-build-funnel-revenue-label {
	font-size: 13px;
	font-family: 'DM Sans', sans-serif;
	font-weight: 600;
	color: #363B4E;
	margin: 0;
	letter-spacing: -0.2px;
}

.wpfnl-mm-revenue-breakdown {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.wpfnl-mm-revenue-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	border: none;
    padding: 10px 0 0;
	border-radius: 8px;
	gap: 12px;
}

.wpfnl-mm-revenue-item-bonus {
	background: #F6F5FA;
}

.wpfnl-mm-revenue-item-content {
	display: flex;
	flex-direction: column;
	gap: 4px;
	flex: 1;
	min-width: 0;
}

.wpfnl-mm-revenue-item-label {
	font-size: 11px;
	font-family: 'DM Sans', sans-serif;
	color: #7A8C9A;
	font-weight: 500;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	display: flex;
	align-items: center;
	gap: 6px;
}

.wpfnl-mm-revenue-item-name {
	font-size: 14px;
	font-family: 'DM Sans', sans-serif;
	color: #363B4E;
	font-weight: 500;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.wpfnl-mm-revenue-badge {
	display: inline-block;
	padding: 2px 6px;
	background: #6E42D3;
	color: #FFFFFF;
	font-size: 9px;
	font-weight: 600;
	border-radius: 3px;
	text-transform: uppercase;
	letter-spacing: 0.3px;
}

.wpfnl-mm-revenue-item-price {
	font-size: 16px;
	font-family: 'DM Sans', sans-serif;
	font-weight: 600;
	color: #363B4E;
	flex-shrink: 0;
}

.wpfnl-mm-revenue-bonus {
	color: #6E42D3;
	font-weight: 700;
}

.wpfnl-mm-revenue-separator {
	height: 1px;
	background: #ECEBF0;
	margin: 6px 0;
}

.wpfnl-mm-revenue-total {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 16px;
	background: #FFFFFF;
	border-radius: 8px;
	gap: 12px;
}

.wpfnl-mm-revenue-total-content {
	display: flex;
	flex-direction: column;
	gap: 4px;
	flex: 1;
}

.wpfnl-mm-revenue-total-label {
	font-size: 11px;
	font-family: 'DM Sans', sans-serif;
	font-weight: 500;
	color: #000;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.wpfnl-mm-revenue-total-value {
	font-size: 24px;
	font-family: 'DM Sans', sans-serif;
	font-weight: 700;
	color: #000;
	letter-spacing: -0.5px;
}

.wpfnl-mm-revenue-boost-inline {
	display: flex;
	gap: 2px;
	border-radius: 6px;
	background: #33a6461a;
    flex-direction: row;
    height: auto;
    justify-content: center;
    align-items: center;
    padding: 16px 12px;
    line-height: 1;
}

.wpfnl-mm-revenue-boost-inline svg {
	color: #33A646;
    width: 12px;
    height: 12px;
}

.wpfnl-mm-revenue-boost-inline span {
	font-size: 12px;
	font-family: 'DM Sans', sans-serif;
	font-weight: 700;
	color: #33A646;
    letter-spacing: 0.4px;
}

/* Right Panel - Preview */
.wpfnl-mm-build-funnel-preview {
	flex: 1;
	background: #FFF;
	border-radius: 16px;
	min-height: 556px;
	display: flex;
	flex-direction: column;
}

/* Tabs */
.wpfnl-mm-build-funnel-tabs {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20px 20px 0;
	gap: 0;
	flex-wrap: wrap;
}

.wpfnl-mm-build-funnel-tab {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
	cursor: pointer;
	flex-shrink: 0;
}

.wpfnl-mm-build-funnel-tab-icon {
	width: 48px;
	height: 48px;
	border-radius: 50%;
	border: 1px solid #F1EFF7;
	display: flex;
	align-items: center;
	justify-content: center;
	color: #6E7A85;
	transition: all 0.3s ease;
}

.wpfnl-mm-build-funnel-tab.active .wpfnl-mm-build-funnel-tab-icon {
	border: 1.5px solid #6E42D3;
	color: #6E42D3;
	box-shadow: 0px 20px 30px rgba(199, 191, 217, 0.3);
}

.wpfnl-mm-build-funnel-tab-label {
	font-size: 12px;
	font-family: 'Inter', sans-serif;
	font-weight: 500;
	color: #6E7A85;
	text-align: center;
	white-space: nowrap;
}

.wpfnl-mm-build-funnel-tab.active .wpfnl-mm-build-funnel-tab-label {
	color: #363B4E;
}

.wpfnl-mm-build-funnel-tab-line {
	width: 16px;
	height: 1px;
	background: #E5E5E5;
	margin: 0 6px;
	margin-bottom: 24px;
	flex-shrink: 0;
}

/* Preview Content */
.wpfnl-mm-build-funnel-preview-content {
	flex: 1;
	padding: 20px;
}

.wpfnl-mm-build-funnel-preview-image {
	width: 100%;
	height: 100%;
	min-height: 400px;
	background: #F6F5FA;
	border-radius: 16px;
	overflow: hidden;
	display: flex;
	align-items: flex-start;
	justify-content: center;
}

.wpfnl-mm-build-funnel-preview-image img {
	width: 100%;
	height: auto;
	object-fit: cover;
	object-position: top center;
	border-radius: 8px;
	box-shadow: 0px 14px 20px #DFDCE8;
}

.wpfnl-mm-build-funnel-preview-placeholder,
.wpfnl-mm-build-funnel-preview-empty {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 100%;
	min-height: 400px;
	color: #7A8C9A;
	text-align: center;
	padding: 40px;
}

.wpfnl-mm-build-funnel-preview-placeholder svg,
.wpfnl-mm-build-funnel-preview-empty svg {
	margin-bottom: 16px;
	opacity: 0.5;
}

.wpfnl-mm-build-funnel-preview-placeholder p,
.wpfnl-mm-build-funnel-preview-empty p {
	font-size: 14px;
	font-family: 'DM Sans', sans-serif;
	margin: 0;
}

.wpfnl-mm-build-funnel-preview-empty h4 {
	font-size: 18px;
	font-family: 'DM Sans', sans-serif;
	font-weight: 600;
	color: #363B4E;
	margin: 0 0 8px 0;
}

/* Buttons */
.wpfnl-mm-build-funnel-buttons {
	display: flex;
	justify-content: space-between;
	align-items: center;
	width: 100%;
}

.wpfnl-mm-btn {
	padding: 10px 24px;
	border-radius: 10px;
	font-size: 16px;
	font-weight: 700;
	font-family: 'DM Sans', sans-serif;
	line-height: 24px;
	cursor: pointer;
	transition: all 0.3s ease;
	border: none;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	height: 44px;
}

.wpfnl-mm-btn-secondary {
	background: #FFF;
	color: #363B4E;
	border: 1px solid #ECEBF0;
	min-width: 87px;
}

.wpfnl-mm-btn-secondary:hover {
	border-color: #6E42D3;
	color: #6E42D3;
}

.wpfnl-mm-btn-skip {
	background: transparent;
	color: #7A8C9A;
	font-weight: 500;
	font-size: 15px;
	padding: 10px;
}

.wpfnl-mm-btn-skip:hover {
	color: #6E42D3;
}

.wpfnl-mm-btn-primary {
	background: #6E42D3;
	color: #FFF;
	font-weight: 600;
	letter-spacing: 0.2px;
	min-width: 146px;
}

.wpfnl-mm-btn-primary svg {
	width: 17px;
	height: 12px;
}

.wpfnl-mm-btn-primary:hover {
	background: #5c36b3;
}

.wpfnl-mm-btn-primary:disabled {
	background: #ccc;
	cursor: not-allowed;
}

.wpfnl-mm-btn-group {
	display: flex;
	gap: 10px;
	align-items: center;
}

/* Spinner */
@keyframes spin {
	from { transform: rotate(0deg); }
	to { transform: rotate(360deg); }
}

.wpfnl-spinner {
	animation: spin 1s linear infinite;
}

/* Help Button */
.wpfnl-mm-help-btn {
	position: fixed;
	bottom: 40px;
	right: 54px;
	z-index: 100;
}

.wpfnl-mm-help-btn-circle {
	width: 34px;
	height: 34px;
	border-radius: 100px;
	background: #201F22;
	border: none;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0px 1px 2px rgba(190, 190, 215, 0.16);
	transition: all 0.3s ease;
}

.wpfnl-mm-help-btn-circle svg {
	width: 16px;
	height: 16px;
}

.wpfnl-mm-help-btn-circle:hover {
	background: #363B4E;
	transform: scale(1.05);
}

/* Create Product Modal */
.wpfnl-modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 10000;
}

.wpfnl-modal-wrapper {
	position: relative;
	width: 90%;
	max-width: 1400px;
	height: 90vh;
	background: #FFF;
	border-radius: 12px;
	overflow: hidden;
	box-shadow: 0px 10px 40px rgba(0, 0, 0, 0.2);
}

.wpfnl-modal-close {
	position: absolute;
	top: 16px;
	right: 16px;
	z-index: 10;
}

.wpfnl-modal-close-btn {
	width: 32px;
	height: 32px;
	border-radius: 50%;
	background: #FFF;
	border: 1px solid #E5E5E5;
	display: flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	transition: all 0.3s ease;
}

.wpfnl-modal-close-btn:hover {
	background: #F6F5FA;
	border-color: #6E42D3;
}

.wpfnl-modal-close-btn svg path {
	transition: stroke 0.3s ease;
}

.wpfnl-modal-close-btn:hover svg path {
	stroke: #6E42D3;
}

.wpfnl-loader {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 40px;
	height: 40px;
	border: 4px solid #F6F5FA;
	border-top-color: #6E42D3;
	border-radius: 50%;
	animation: spin 1s linear infinite;
}

#wpfnl-create-product {
	width: 100%;
	height: 100%;
}

#wpfnl-create-product iframe {
	width: 100%;
	height: 100%;
	border: none;
}
</style>
