export const welcomeSection = `
    <section class="setup-wizard__welcome-section-container">
        <div class="setup-wizard__welcome-text-content">
        <h1 class="setup-wizard__welcome-heading setup-wizard__heading-one">
            Hello, welcome to 
            <span class="setup-wizard__heading-one-highlight">WPFunnels</span>
        </h1>
        <p class="setup-wizard__welcome-description setup-wizard__description">
            Hey there! Let's get you started with WPFunnels so that you can create high-converting funnels to collect leads and increase sales.
        </p>
        </div>

        <!-- video container -->
        <div class="setup-wizard__welcome-video-container">
            <div id="video_play_button" class="setup-wizard__video-play-button">
                <button class="icon-button" onclick="handleOpenVideo()">
                    <svg width="100" height="70" viewBox="0 0 100 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M97.7467 10.9613C97.1734 8.84245 96.055 6.91079 94.5028 5.35872C92.9507 3.80666 91.0189 2.68836 88.9001 2.11523C81.1428 0 49.9254 0 49.9254 0C49.9254 0 18.7069 0.0641905 10.9508 2.17942C8.83206 2.75265 6.90045 3.87099 5.34839 5.42304C3.79634 6.9751 2.678 8.90671 2.10477 11.0255C-0.241546 24.8081 -1.15183 45.8081 2.16896 59.0387C2.74219 61.1575 3.86053 63.0891 5.41258 64.6412C6.96464 66.1932 8.89625 67.3115 11.015 67.8848C18.7717 70 49.9896 70 49.9896 70C49.9896 70 81.2076 70 88.9642 67.8848C91.083 67.3115 93.0146 66.1932 94.5667 64.6412C96.1187 63.0891 97.2371 61.1575 97.8103 59.0387C100.284 45.2372 101.047 24.2499 97.7461 10.9619" fill="#FF0000"></path>
                        <path d="M39.9895 50.0004L65.887 35.0006L39.9895 20.0002V50.0004Z" fill="white"></path>
                    </svg>
                </button>
            </div>
            <div id="setup_video" class="setup-wizard__welcome-video-iframe" style="display: none">
                <iframe id="recommendation-video_set" title="Video"></iframe>
            </div>

            <img id="recommendation-preview" class="setup-wizard__welcome-video-preview" loading="lazy" src="${window.setup_wizard_obj.wizard_video_poster}" alt="preview image">
        </div>

        <!-- setup wizard buttons -->
        <div class="setup-wizard__main-buttons">
            <button onclick="handleWelcomeButton()" class="setup-wizard__button-left">
                Let's Start
            </button>
            <a href="https://getwpfunnels.com/docs/getting-started-with-wpfunnels/" target="_blank" class="setup-wizard__button-right">
                Check the guide
            </a>
        </div>
    </section>
`;

export const featuresSection = `
<section class="setup-wizard__features-section-container">
    <div class="setup-wizard__features-text-content">
        <h1 class="setup-wizard__feature-heading setup-wizard__heading-one">
            <span class="setup-wizard__heading-one-highlight">WPFunnels Features</span>
        </h1>
        <p class="setup-wizard__feature-description setup-wizard__description">
            WPFunnels is the easiest funnel builder for WordPress & WooCommerce!
        </p>
    </div>

    <!--pro features -->
    <div class="setup-wizard__pro-features-section-container">
        <div class="setup-wizard__pro-features">
            <div class="setup-wizard__pro-feature-lists">
                

                

                <div class="setup-wizard__single-pro-feature">
                    <svg fill="none" width="21" height="21" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path fill="#52B588" d="M3.5.2A3.3 3.3 0 00.2 3.5a.9.9 0 001.8 0A1.5 1.5 0 013.5 2a.9.9 0 000-1.8zM.2 12.5a3.3 3.3 0 003.3 3.3.9.9 0 000-1.8A1.5 1.5 0 012 12.5a.9.9 0 00-1.8 0zM12.5.2a3.3 3.3 0 013.3 3.3.9.9 0 01-1.8 0A1.5 1.5 0 0012.5 2a.9.9 0 110-1.8zM2 6.8a.9.9 0 00-1.8 0v2.4a.9.9 0 001.8 0V6.8zm8.1-5.7a.9.9 0 01-.9.9H6.8a.9.9 0 010-1.8h2.4a.9.9 0 01.9.9zM5.3 3.2a2.1 2.1 0 00-2.1 2.1v5.4c0 1.16.94 2.1 2.1 2.1h1.5a.9.9 0 000-1.8H5.3a.3.3 0 01-.3-.3V5.3a.3.3 0 01.3-.3h5.4a.3.3 0 01.3.3v1.5a.9.9 0 101.8 0V5.3a2.1 2.1 0 00-2.1-2.1H5.3z"/><path fill="#52B588" d="M9.278 8.551a.6.6 0 00-.727.727l1.346 5.413a.6.6 0 001.006.28l1.397-1.398 1.01 1.01a.6.6 0 00.848 0l.424-.425a.6.6 0 000-.848l-1.009-1.01 1.397-1.397a.6.6 0 00-.28-1.006L9.279 8.551z"/></svg>

                    <p class="setup-wizard__single-pro-feature-title">
                        Visual Funnel Canvas
                    </p>

                    <p class="setup-wizard__single-pro-feature-description-text">Plan and organize your funnels easily on a visual canvas.</p>
                </div>

                <div class="setup-wizard__single-pro-feature">
                    <svg fill="none" width="21" height="21" viewBox="0 0 17 19" xmlns="http://www.w3.org/2000/svg"><path fill="#EE8134" stroke="#EE8134" stroke-width=".4" d="M15.388 11.89L9.167 6.663a1.818 1.818 0 00-1.999-.234c-.32.162-.586.415-.768.728a1.91 1.91 0 00-.253 1.035l.39 8.175c.014.352.136.69.349.97.212.277.505.481.837.583a1.648 1.648 0 001.258-.102 1.68 1.68 0 00.59-.503l1.347-1.813a1.444 1.444 0 011.159-.585h2.235c.347 0 .686-.107.97-.31.284-.202.5-.488.618-.82.118-.332.132-.693.04-1.034a1.714 1.714 0 00-.552-.868v.004zm-.697 1.454a.383.383 0 01-.38.27h-2.234c-.423 0-.84.1-1.22.292a2.76 2.76 0 00-.965.812l-1.347 1.813a.39.39 0 01-.443.144.384.384 0 01-.2-.137.395.395 0 01-.081-.23l-.39-8.175a.562.562 0 01.622-.589.55.55 0 01.295.129l6.222 5.229a.387.387 0 01.121.442zM6.144 3.398V1.654a.66.66 0 01.188-.462.637.637 0 01.91 0 .66.66 0 01.188.462v1.744a.66.66 0 01-.188.462.637.637 0 01-.91 0 .66.66 0 01-.188-.462zM4.563 5.163A.647.647 0 014 5.497a.625.625 0 01-.312-.087l-1.714-.973a.645.645 0 01-.316-.392.665.665 0 01.22-.7.639.639 0 01.722-.05l1.714.975a.646.646 0 01.307.394.665.665 0 01-.058.499zm-.63 2.302a.664.664 0 01.035.5.65.65 0 01-.323.378l-1.714.871a.636.636 0 01-.687-.072.664.664 0 01-.184-.79.649.649 0 01.296-.308l1.714-.872a.634.634 0 01.863.293zm5.708-2.903a.664.664 0 01-.035-.5.65.65 0 01.323-.377l1.714-.872a.633.633 0 01.489-.032c.16.055.293.173.37.327a.664.664 0 01.034.497.65.65 0 01-.319.378l-1.714.872a.627.627 0 01-.625-.03.652.652 0 01-.237-.263z"/></svg>

                    <p class="setup-wizard__single-pro-feature-title">
                        One Click Upsell and Downsell
                    </p>
                    <p class="setup-wizard__single-pro-feature-description-text">Using one-click upsell offers to generate more sales & revenue.</p>
                </div>

                <div class="setup-wizard__single-pro-feature">
                    <svg fill="none" width="21" height="21" viewBox="0 0 21 23" xmlns="http://www.w3.org/2000/svg"><path fill="#6E42D3" fill-rule="evenodd" stroke="#6E42D3" stroke-width=".2" d="M14.73 20.386c.295 0 .535-.25.535-.56 0-.31-.24-.56-.536-.56a.548.548 0 00-.536.56c0 .31.24.56.536.56zm0 1.68c1.184 0 2.144-1.003 2.144-2.24 0-1.237-.96-2.24-2.145-2.24-1.184 0-2.144 1.003-2.144 2.24 0 1.237.96 2.24 2.144 2.24zm-6.433-1.68c.296 0 .536-.25.536-.56 0-.31-.24-.56-.536-.56a.548.548 0 00-.537.56c0 .31.24.56.537.56zm0 1.68c1.184 0 2.144-1.003 2.144-2.24 0-1.237-.96-2.24-2.144-2.24-1.185 0-2.145 1.003-2.145 2.24 0 1.237.96 2.24 2.145 2.24zm-.805-14.56c0-.463.36-.84.804-.84h3.217c.444 0 .804.377.804.84 0 .464-.36.84-.804.84H8.296c-.444 0-.804-.376-.804-.84zm0 3.36c0-.464.36-.84.804-.84h5.362c.444 0 .804.376.804.84 0 .464-.36.84-.804.84H8.296c-.444 0-.804-.376-.804-.84z" clip-rule="evenodd"/><path fill="#6E42D3" fill-rule="evenodd" stroke="#6E42D3" stroke-width=".2" d="M.657 1.44a.783.783 0 011.115-.233l1.252.872c.4.279.683.707.79 1.197l2.206 10.14c.139.637.681 1.09 1.307 1.09h8.372c.626 0 1.168-.453 1.307-1.09l1.706-7.84c.19-.876-.447-1.71-1.307-1.71H9.1c-.444 0-.804-.376-.804-.84 0-.464.36-.84.804-.84h8.305c1.892 0 3.295 1.835 2.875 3.762l-1.706 7.84c-.305 1.402-1.498 2.397-2.875 2.397H7.327c-1.377 0-2.57-.995-2.876-2.397L2.245 3.648a.28.28 0 00-.113-.171L.88 2.605A.863.863 0 01.657 1.44z" clip-rule="evenodd"/></svg>

                    <p class="setup-wizard__single-pro-feature-title">
                        Unlimited Order Bump
                    </p>
                    <p class="setup-wizard__single-pro-feature-description-text">Increase your sales revenue with smart order bump offers.</p>
                </div>

                <div class="setup-wizard__single-pro-feature">
                    <svg fill="none" width="21" height="21" viewBox="0 0 23 22" xmlns="http://www.w3.org/2000/svg"><path fill="#DF52FF" stroke="#DF52FF" stroke-width=".3" d="M14.125 15.283a.525.525 0 01-.525-.525v-6.3c0-.29.235-.525.525-.525H15.7a2.1 2.1 0 011.745 3.268 2.101 2.101 0 01-.695 4.082h-2.625zm.525-1.05h2.1a1.05 1.05 0 100-2.1h-2.1v2.1zm0-3.15h1.05a1.05 1.05 0 100-2.1h-1.05v2.1zm-2.152-8.4h6.877A2.625 2.625 0 0122 5.308v13.65a2.625 2.625 0 01-2.625 2.625h-6.3a2.626 2.626 0 01-2.584-2.16c-.182.04-.372.06-.566.06h-6.3A2.625 2.625 0 011 16.858V3.208A2.625 2.625 0 013.625.583h6.3c1.27 0 2.33.902 2.572 2.1zm.052 1.05v13.125c0 .86-.412 1.622-1.05 2.1 0 .87.705 1.575 1.575 1.575h6.3c.87 0 1.575-.705 1.575-1.575V5.308c0-.87-.705-1.575-1.575-1.575H12.55zm-4.662 6.822a.505.505 0 01-.063.003h-2.1a.505.505 0 01-.063-.003l-.5 1.248a.525.525 0 11-.974-.39l2.1-5.25a.525.525 0 01.974 0l2.1 5.25a.525.525 0 11-.974.39l-.5-1.248zM7.47 9.508l-.695-1.736-.695 1.736h1.39zm2.455-7.875h-6.3c-.87 0-1.575.705-1.575 1.575v13.65c0 .87.705 1.575 1.575 1.575h6.3c.87 0 1.575-.705 1.575-1.575V3.208c0-.87-.705-1.575-1.575-1.575zm-4.2 15.75a.525.525 0 010-1.05h2.1a.525.525 0 010 1.05h-2.1zm9.45 2.1a.525.525 0 010-1.05h2.1a.525.525 0 010 1.05h-2.1z"/></svg>

                    <p class="setup-wizard__single-pro-feature-title">
                        A/B Split Testing
                    </p>
                    <p class="setup-wizard__single-pro-feature-description-text">Run A/B tests for every funnel step to determine what works best.</p>
                </div>

                <div class="setup-wizard__single-pro-feature">
                    <svg fill="none" width="21" height="21"  viewBox="0 0 23 23" xmlns="http://www.w3.org/2000/svg"><path stroke="#2850FF" stroke-miterlimit="10" stroke-width="1.5" d="M22 10.084h-9.193V.89C17.865.89 22 5.025 22 10.084z"/><path stroke="#2850FF" stroke-miterlimit="10" stroke-width="1.5" d="M10.193 12.698h9.15c0 5.053-4.097 9.193-9.15 9.193S1 17.75 1 12.698s4.14-9.15 9.193-9.15v9.15z"/><path stroke="#2850FF" stroke-miterlimit="10" stroke-width="1.5" d="M3.723 19.167l6.47-6.47-6.47-6.469"/></svg>

                    <p class="setup-wizard__single-pro-feature-title">
                        Funnel Analytics
                    </p>
                    <p class="setup-wizard__single-pro-feature-description-text">Make data-driven business decisions with detailed analytics.</p>
                </div>

                <div class="setup-wizard__single-pro-feature">
                    <svg fill="none" width="21" height="21" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg"><path fill="#EE8134" stroke="#EE8134" stroke-width=".2" d="M11.5 1H7a6 6 0 00-6 6v4.5a6 6 0 006 6h4.5a6 6 0 006-6V7a6 6 0 00-6-6zm3 10.5a3 3 0 01-3 3H7a3 3 0 01-3-3V7a3 3 0 013-3h4.5a3 3 0 013 3v4.5zM31 1h-4.5a6 6 0 00-6 6v4.5a6 6 0 006 6H31a6 6 0 006-6V7a6 6 0 00-6-6zm3 10.5a3 3 0 01-3 3h-4.5a3 3 0 01-3-3V7a3 3 0 013-3H31a3 3 0 013 3v4.5zm-22.5 9H7a6 6 0 00-6 6V31a6 6 0 006 6h4.5a6 6 0 006-6v-4.5a6 6 0 00-6-6zm3 10.5a3 3 0 01-3 3H7a3 3 0 01-3-3v-4.5a3 3 0 013-3h4.5a3 3 0 013 3V31zM31 20.5h-4.5a6 6 0 00-6 6V31a6 6 0 006 6H31a6 6 0 006-6v-4.5a6 6 0 00-6-6zM34 31a3 3 0 01-3 3h-4.5a3 3 0 01-3-3v-4.5a3 3 0 013-3H31a3 3 0 013 3V31z"/></svg>

                    <p class="setup-wizard__single-pro-feature-title">
                        Pre-built Templates
                    </p>
                    <p class="setup-wizard__single-pro-feature-description-text">Create and send regular campaigns and email sequences easily.</p>
                </div>

                <div class="setup-wizard__single-pro-feature">
                    <svg fill="none" width="21" height="21" viewBox="0 0 35 35" xmlns="http://www.w3.org/2000/svg"><path fill="#05E4BA" d="M31.693 23.914a7.304 7.304 0 00-6.906-4.958h-5.832v-1.58a8.749 8.749 0 10-2.917 0v1.58h-5.832A7.304 7.304 0 003.3 23.914a5.833 5.833 0 103.568-.487 4.375 4.375 0 013.338-1.554h5.832v1.643a5.832 5.832 0 102.917 0v-1.643h5.832a4.374 4.374 0 013.338 1.55 5.833 5.833 0 103.568.487v.004zM11.663 8.749a5.833 5.833 0 1111.666 0 5.833 5.833 0 01-11.665 0zM8.748 29.163a2.917 2.917 0 11-5.833 0 2.917 2.917 0 015.833 0zm11.666 0a2.916 2.916 0 11-5.833 0 2.916 2.916 0 015.833 0zm8.749 2.917a2.917 2.917 0 110-5.834 2.917 2.917 0 010 5.834z"/></svg>

                    <p class="setup-wizard__single-pro-feature-title">
                        Conditional Funnel
                    </p>
                    <p class="setup-wizard__single-pro-feature-description-text">Set conditions to send buyers into custom funnels from your online shop.</p>
                </div>

                <div class="setup-wizard__single-pro-feature">
                    <svg fill="none" width="22" height="22" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_1_18352)"><path fill="#F6CA45" stroke="#F6CA45" stroke-width=".2" d="M9.997 8.692l1.511-.147A2.335 2.335 0 0013.667 10 2.336 2.336 0 0016 7.666a2.336 2.336 0 00-2.333-2.333 2.335 2.335 0 00-2.288 1.886l-1.639.158a3.327 3.327 0 00-.638-.97l1.226-1.851a2.3 2.3 0 00.672.11 2.336 2.336 0 002.333-2.333A2.336 2.336 0 0011 0a2.336 2.336 0 00-2.333 2.333c0 .568.212 1.082.549 1.486L8.019 5.627a3.302 3.302 0 00-1.352-.294c-.483 0-.938.108-1.353.294L4.117 3.819a2.31 2.31 0 00.55-1.486A2.336 2.336 0 002.333 0 2.336 2.336 0 000 2.333a2.336 2.336 0 002.333 2.334c.236 0 .458-.046.672-.111l1.226 1.85a3.311 3.311 0 00-.898 2.26c0 .753.261 1.441.684 2l-.742.87a2.314 2.314 0 00-.941-.203 2.336 2.336 0 00-2.333 2.334A2.336 2.336 0 002.334 16a2.336 2.336 0 002.333-2.333c0-.467-.141-.9-.378-1.266l.73-.855A3.3 3.3 0 006.667 12c.738 0 1.413-.248 1.966-.655l1.578 1.366a2.32 2.32 0 00-.21.956 2.336 2.336 0 002.333 2.334 2.336 2.336 0 002.333-2.334 2.336 2.336 0 00-2.333-2.333c-.46 0-.888.139-1.25.37l-1.559-1.349c.291-.49.468-1.054.473-1.662v-.001zm3.67-2.025a1.001 1.001 0 010 2 1.001 1.001 0 010-2zM11 1.333a1.001 1.001 0 010 2 1.001 1.001 0 010-2zm-9.667 1a1.001 1.001 0 012 0 1.001 1.001 0 01-2 0zm1 12.334a1.001 1.001 0 010-2 1.001 1.001 0 010 2zm4.334-4c-1.103 0-2-.898-2-2 0-1.103.897-2 2-2 1.102 0 2 .897 2 2 0 1.102-.898 2-2 2zm5.666 2a1.001 1.001 0 010 2 1.001 1.001 0 010-2z"></path></g><defs><clipPath id="clip0_1_18352"><path fill="#fff" d="M0 0h16v16H0z"></path></clipPath></defs></svg>

                    <p class="setup-wizard__single-pro-feature-title">
                        Integrations
                    </p>
                    <p class="setup-wizard__single-pro-feature-description-text">Integrate with several CRM and automation tools.</p>
                </div>
            </div>
        </div>

        <div class="setup-wizard__pro-features-price">
            <p>
                Starting at
                <span class="setup-wizard__pro-features-price-amount">$97.00</span>/year

                <span style="display: none" class="setup-wizard_pro-features-price-savings">Normally $77.60/year </span>
            </p>
            <div style="display: none" class="setup-wizard__pro-features-price-tag">
                Save 20%	
            </div>
        </div>

        <div class="setup-wizard__pro-feature-list-button-container">
            <a href="https://getwpfunnels.com/features/" target="_blank" class="setup-wizard__feature-list-button">
                Check All Features
                <svg width="17" height="14" viewBox="0 0 17 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15.171 7.02277L1.47856 6.65834" stroke="#73707D" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
                <path d="M10.0348 11.8971L15.1709 7.008L10.2614 1.85151" stroke="#73707D" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </a>

            <a href="https://getwpfunnels.com/pricing/" target="_blank" class="setup-wizard__pro-feature-list-button">
                Upgrade To Pro Now
                <svg width="17" height="14" viewBox="0 0 17 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15.171 7.02277L1.47856 6.65834" stroke="#ffffff" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M10.0348 11.8971L15.1709 7.008L10.2614 1.85151" stroke="#ffffff" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </a>
        </div>
    </div>
</section>
`;

export const welcomeButtonSection = `
<section class="setup-wizard__footer-buttons setup-wizard-flex-col">
    <h3>Ready to start using WPFunnels?</h3>
    <button onclick="handleWelcomeButton()" class="setup-wizard__button-left">
        Let's Start
    </button>
</section>
`;

export const doneSection = `
<!-- done section container -->
<section class="setup-wizard__done-section-container">
    <!-- text content -->
    <div class="setup-wizard__done-text-content setup-wizard__done-text-content--done-icon">
    <img src="${window.setup_wizard_obj?.done_icon}" alt="done">
    <h1 class="setup-wizard__done-heading setup-wizard__heading-one">
        You Are
        <span class="setup-wizard__heading-one-highlight">Done</span>
    </h1>
    </div>

    <!-- testimonial container -->
    <div class="setup-wizard__testimonial">
    <h2 class="setup-wizard__testimonial-title">Testimonials</h2>
    <div class="setup-wizard__testimonial-card">
        <div class="setup-wizard__testimonial-single-card">
        <p class="setup-wizard__testimonial-text-content">
            I tested several other funnel plugins in WordPress, and I found that this plugin hands down is the best.
        </p>
        <p class="setup-wizard__testimonial-text-author">
            - Darrel Wilson
        </p>
        <img class="setup-wizard__testimonial-quote-icon" src="${window.setup_wizard_obj?.quote_img}" alt="Quote">
        </div>
        <div class="setup-wizard__testimonial-single-card">
        <p class="setup-wizard__testimonial-text-content">
            The fact that it’s visual makes it even better – because you can easily see non-technical customers wanting to copy a previous funnel but make a quick little tweak. Perfect space for WPFunnels.
        </p>
        <p class="setup-wizard__testimonial-text-author">
            - Chris Lema
        </p>
        <img class="setup-wizard__testimonial-quote-icon" src="${window.setup_wizard_obj?.quote_img}" alt="Quote">
        </div>
    </div>
    </div>

    <!-- subscribe button -->
    <div class="setup-wizard__subscribe-button-container">
    <!-- switcher -->
    <label class="setup-wizard__switch">
        <input type="checkbox" checked="" id="setup-wizard__switch-for-collect-email">
        <span class="setup-wizard__switch-slider setup-wizard__switch-round"></span>
    </label>
    <p>
        Opt-in to receive tips, discounts, and recommendations from
        the WPFunnels Team directly in your inbox.
    </p>
    </div>
</section>
`;

export const doneButtonSection = `
<!-- setup wizard buttons -->
<section class="setup-wizard__footer-buttons">
    <button onclick="handleLastStepButton('/wp-admin/admin.php?page=wp_funnels')" class="setup-wizard__button-left">
        Let’s create your first funnel
    </button>
    <button onclick="handleLastStepButton('https://getwpfunnels.com/pricing/')" class="setup-wizard__button-right">
        Upgrade To Pro
    </button>
</section>
`;

export const funnelType = `
<section class="setup-wizard__funnel-type setup-wizard__card">
    <h1 class="setup-wizard__done-heading setup-wizard__heading-one">
        What do you want to accomplish using
        <span class="setup-wizard__heading-one-highlight">WPFunnels?</span>
    </h1>
    <div class="input-custom-wrapper">
        <span class="setup-wizard__radiobtn">
            <input id="lead-funnel" type="radio" name="funnel-type" value="lead" onchange="handleFunnelType(event)">
            <label for="lead-funnel">
                <strong>Lead Funnel -</strong> Get More Leads
            </label>
        </span>
        
        <span class="setup-wizard__radiobtn">
            <input id="sales-funnel" type="radio" name="funnel-type" value="sales" checked onchange="handleFunnelType(event)">
            <label for="sales-funnel">
                <strong>Sales Funnel -</strong> Complete Funnel 
            </label>
        </span>
    </div>
</section>
`;

export const pageBuilderSelection = `
<section class="setup-wizard__card setup-wizard__page-builder">
    <h2 class="setup-wizard__done-heading setup-wizard__heading-one">
        What <span class="setup-wizard__heading-one-highlight">page builder</span> would you like to use?
    </h2>

    <div class="setup-wizard__page-builder-list">
        <div class="setup-wizard__single-page-builder setup-wizard__radiobtn">
            <input id="bricks" type="radio" name="page-builder" value="bricks" onchange="handlePageBuilderSelect(event)">
            <label for="bricks">
                <div class="setup-wizard__page-builder-info">
                    <div class="setup-wizard__page-builder-logo">
                        <img src="${window.setup_wizard_obj.bricks_img}" alt="Bricks Logo" id="wpfnl-mm-bricks-logo"/>
                    </div>
                    <div class="setup-wizard__page-builder-text-wrapper">
                        <h3 class="setup-wizard__page-builder-heading">Bricks Builder</h3>
                        <p class="setup-wizard__page-builder-subheading">Easy page builder that comes with the Bricks theme.</p>
                    </div>
                </div>
            </label>
        </div>

        <div class="setup-wizard__single-page-builder setup-wizard__radiobtn">
            <input id="elementor" type="radio" name="page-builder" value="elementor" onchange="handlePageBuilderSelect(event)">
            <label for="elementor">
                <div class="setup-wizard__page-builder-info">
                    <div class="setup-wizard__page-builder-logo">
                        <img src="${window.setup_wizard_obj.elementor_img}" alt="Elementor Logo" id="wpfnl-mm-elementor-logo"/>
                    </div>
                    <div class="setup-wizard__page-builder-text-wrapper">
                        <h3 class="setup-wizard__page-builder-heading">Elementor</h3>
                        <p class="setup-wizard__page-builder-subheading">The most popular drag and drop page builder in WordPress.</p>
                    </div>
                </div>
            </label>
        </div>

        <div class="setup-wizard__single-page-builder setup-wizard__radiobtn">
            <input id="divi" type="radio" name="page-builder" value="divi-builder" onchange="handlePageBuilderSelect(event)">
            <label for="divi">
                <div class="setup-wizard__page-builder-info">
                    <div class="setup-wizard__page-builder-logo">
                        <img src="${window.setup_wizard_obj.divi_img}" alt="Divi Logo" id="wpfnl-mm-divi-logo"/>
                    </div>
                    <div class="setup-wizard__page-builder-text-wrapper">
                        <h3 class="setup-wizard__page-builder-heading">Divi Page Builder</h3>
                        <p class="setup-wizard__page-builder-subheading">Visual page editor from Divi to design pages real-time.</p>
                    </div>
                </div>
            </label>
        </div>

        <div class="setup-wizard__single-page-builder setup-wizard__radiobtn">
            <input id="oxygen" type="radio" name="page-builder" value="oxygen" onchange="handlePageBuilderSelect(event)">
            <label for="oxygen">
                <div class="setup-wizard__page-builder-info">
                    <div class="setup-wizard__page-builder-logo">
                        <img src="${window.setup_wizard_obj.oxygen_img}" alt="Oxygen Logo" id="wpfnl-mm-oxygen-logo"/>
                    </div>
                    <div class="setup-wizard__page-builder-text-wrapper">
                        <h3 class="setup-wizard__page-builder-heading">Oxygen Builder</h3>
                        <p class="setup-wizard__page-builder-subheading">A visual page builder to design pages in WordPress.</p>
                    </div>
                </div>
            </label>
        </div>

        <div class="setup-wizard__single-page-builder setup-wizard__radiobtn">
            <input id="gutenberg" type="radio" name="page-builder" value="gutenberg" onchange="handlePageBuilderSelect(event)">
            <label for="gutenberg">
                <div class="setup-wizard__page-builder-info">
                    <div class="setup-wizard__page-builder-logo">
                        <img src="${window.setup_wizard_obj.gb_builder_img}" alt="Gutenberg Logo" id="wpfnl-mm-gutenberg-logo"/>
                    </div>
                    <div class="setup-wizard__page-builder-text-wrapper">
                        <h3 class="setup-wizard__page-builder-heading">Gutenberg</h3>
                        <p class="setup-wizard__page-builder-subheading">The default block-based page editor in WordPress.</p>
                    </div>
                </div>
            </label>
        </div>

        <div class="setup-wizard__single-page-builder setup-wizard__radiobtn">
            <input id="other" type="radio" name="page-builder" value="other" onchange="handlePageBuilderSelect(event)">
            <label for="other">
                <div class="setup-wizard__page-builder-info">
                    <div class="setup-wizard__page-builder-logo">
                        <img src="${window.setup_wizard_obj.others_builder_img}" alt="Other Logo" id="wpfnl-mm-other-logo"/>
                    </div>
                    <div class="setup-wizard__page-builder-text-wrapper">
                        <h3 class="setup-wizard__page-builder-heading">Other</h3>
                        <p class="setup-wizard__page-builder-subheading">You can use any page builder that you want.</p>
                    </div>
                </div>
            </label>
        </div>
    </div>
</section>
`;


export const essentialPlugins = `
<section class="setup-wizard__card setup-wizard__essential-plugins">
    <h2 class="setup-wizard__done-heading setup-wizard__heading-one">
        Lets install some
        <span class="setup-wizard__heading-one-highlight"> Required plugins</span>
    </h2>
    <div class="setup-wizard__page-builder-list setup-wizard-essential">
        <div class="setup-wizard__single-page-builder setup-wizard__radiobtn" >
            <input id="plugin-wc" type="radio" name="essential-plugin6" value="woocommerce" checked>
            <label for="plugin-wc">
                <div class="setup-wizard__page-builder-info">
                    <div class="setup-wizard__page-builder-logo">
                        <img src="${window.setup_wizard_obj.wc_logo}" alt="WooCommerce Logo" id="plugin-wpfnl-mm-wooCommerce-logo"/>
                    </div>
                    <div class="setup-wizard__page-builder-text-wrapper">
                        <h3 class="setup-wizard__page-builder-heading">WooCommerce</h3>
                        <p class="setup-wizard__page-builder-subheading">An open-source e-commerce plugin for WordPress.</p>
                    </div>
                    <span class="wpfnl-mm-required-tag">Required</span>
                </div>
            </label>
        </div>

        <div class="setup-wizard__single-page-builder setup-wizard__radiobtn">
            <input id="plugin-bricks" type="radio" name="essential-plugin5" value="bricks" checked>
            <label for="plugin-bricks">
                <div class="setup-wizard__page-builder-info">
                    <div class="setup-wizard__page-builder-logo">
                        <img src="${window.setup_wizard_obj.bricks_img}" alt="Bricks Logo" id="plugin-wpfnl-mm-bricks-logo"/>
                    </div>
                    <div class="setup-wizard__page-builder-text-wrapper">
                        <h3 class="setup-wizard__page-builder-heading">Bricks</h3>
                        <p class="setup-wizard__page-builder-subheading">An innovative, community-driven, visual site builder for WordPress.</p>
                    </div>
                    <span class="wpfnl-mm-required-tag">Required</span>
                </div>
            </label>
        </div>

        <div class="setup-wizard__single-page-builder setup-wizard__radiobtn">
            <input id="plugin-elementor" type="radio" name="essential-plugin4" value="elementor" checked>
            <label for="plugin-elementor">
                <div class="setup-wizard__page-builder-info">
                    <div class="setup-wizard__page-builder-logo">
                        <img src="${window.setup_wizard_obj.elementor_img}" alt="Elementor Logo" id="plugin-wpfnl-mm-elementor-logo"/>
                    </div>
                    <div class="setup-wizard__page-builder-text-wrapper">
                        <h3 class="setup-wizard__page-builder-heading">Elementor</h3>
                        <p class="setup-wizard__page-builder-subheading">Elementor's intuitive website builder makes it easy for anyone.</p>
                    </div>
                    <span class="wpfnl-mm-required-tag">Required</span>
                </div>
            </label>
        </div>

        <div class="setup-wizard__single-page-builder setup-wizard__radiobtn">
            <input id="plugin-divi" type="radio" name="essential-plugin3" value="divi-builder" checked>
            <label for="plugin-divi">
                <div class="setup-wizard__page-builder-info">
                    <div class="setup-wizard__page-builder-logo">
                        <img src="${window.setup_wizard_obj.divi_img}" alt="Divi Logo" id="plugin-wpfnl-mm-divi-logo"/>
                    </div>
                    <div class="setup-wizard__page-builder-text-wrapper">
                        <h3 class="setup-wizard__page-builder-heading">Divi</h3>
                        <p class="setup-wizard__page-builder-subheading">Complete design framework for wordpress page creation.</p>
                    </div>
                    <span class="wpfnl-mm-required-tag">Required</span>
                </div>
            </label>
        </div>

        <div class="setup-wizard__single-page-builder setup-wizard__radiobtn">
            <input id="plugin-oxygen" type="radio" name="essential-plugin2" value="oxygen" checked>
            <label for="plugin-oxygen">
                <div class="setup-wizard__page-builder-info">
                    <div class="setup-wizard__page-builder-logo">
                        <img src="${window.setup_wizard_obj.oxygen_img}" alt="Oxygen Logo" id="plugin-wpfnl-mm-oxygen-logo"/>
                    </div>
                    <div class="setup-wizard__page-builder-text-wrapper">
                        <h3 class="setup-wizard__page-builder-heading">Oxygen</h3>
                        <p class="setup-wizard__page-builder-subheading">Use Oxygen plugin to visually design your WordPress page</p>
                    </div>
                    <span class="wpfnl-mm-required-tag">Required</span>
                </div>
            </label>
        </div>

        <div class="wpfnl-mm-setup-single-plugin wpfnl-mm-mail-mint-setup" data-plugin="mail-mint" id="setup-wizard__mail-mint">
            <div>
                <span class="wpfnl-mm-checkbox no-title">
                    <input type="checkbox" name="select-mailmint" id="plugin-select-mailmint" onchange="handleSelectMailMint(event)">
                    <label for="plugin-select-mailmint"></label>
                </span>
            </div>
            <div class="wpfnl-mm-setup-plugin-mm-wrapper">
                <div class="wpfnl-mm-setup-plugin-logo">
                    <img src="${window.setup_wizard_obj.mail_mint_logo}"  alt="Mail Mint Logo" id="plugin-wpfnl-mm-mm-logo"/>
                </div>
                <div class="wpfnl-mm-setup-plugin-info">
                    <h3 class="wpfnl-mm-setup-plugin-heading">Mail Mint</h3>
                    <p class="wpfnl-mm-setup-plugin-subheading">Collect leads & use email automation.</p>
                </div>
            </div>
        </div>

        <div class="wpfnl-mm-setup-single-plugin wpfnl-mm-qubely-setup" data-plugin="qubely" id="setup-wizard__qubely">
            <div>
                <span class="wpfnl-mm-checkbox no-title">
                    <input type="checkbox" name="select-qubely" id="plugin-select-qubely" onchange="handleSelectQubely(event)">
                    <label for="plugin-select-qubely"></label>
                </span>
            </div>
            <div class="wpfnl-mm-setup-plugin-mm-wrapper">
                <div class="wpfnl-mm-setup-plugin-logo">
                    <img src="${window.setup_wizard_obj.qubely_img}"  alt="Qubely Logo" id="plugin-wpfnl-mm-qubely-logo"/>
                </div>
                <div class="wpfnl-mm-setup-plugin-info">
                    <h3 class="wpfnl-mm-setup-plugin-heading">Qubely</h3>
                    <p class="wpfnl-mm-setup-plugin-subheading">Advanced Gutenberg blocks.</p>
                </div>
            </div>
        </div>

        <div class="wpfnl-mm-setup-no-plugin-needed">
            <img src="${window.setup_wizard_obj.no_plugin_image}"  alt="No Plugin 	Needed" id="plugin-wpfnl-mm-no-plugin-img"/>
        </div>
    </div>
</section>
`;

export const settingsFooter = `
<section class="setup-wizard__footer-buttons">
    <button onclick="handlePrevious()" class="setup-wizard__button-right" id="setup-wizard__settings-back-btn"> Previous </button>
    <button onclick="handleEssentialPlugins()" id="setup-wizard__install-btn" disabled class="setup-wizard__button-left">
        Next
        <span class="wpfnl-loader"></span>
    </button>
    <button onclick="handleContinue()" id="setup-wizard__continue-btn" style="display: none;" class="setup-wizard__button-left">Next</button>
</section>
`;