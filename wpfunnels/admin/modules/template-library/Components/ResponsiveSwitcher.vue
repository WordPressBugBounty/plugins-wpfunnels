<template>
  <div class="responsive-switcher">
    <button type="button"  class="desktop-view" :class="view === 'desktop' ? 'active' : ''" data-target="desktop-view" @click="setView('desktop')">
        <svg fill="none" width="23" height="20" viewBox="0 0 23 20" xmlns="http://www.w3.org/2000/svg"><path stroke="#B5BAC4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 1h-18c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h18c.621 0 1.125-.504 1.125-1.125V2.125c0-.621-.504-1.125-1.125-1.125zM5.628 18.999h11.25M7.875 14.5V19m6.75-4.5V19"/></svg>
    </button>

    <button type="button" class="tablet-view" :class="view === 'tablet' ? 'active' : ''"  data-target="tablet-view" @click="setView('tablet')">
        <svg fill="none" width="18" height="20" viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><rect width="14.864" height="18.5" x="1.568" y=".75" stroke="#B5BAC4" stroke-width="1.5" rx="2.25"/><circle cx="8.545" cy="15" r="1.364" fill="#B5BAC4"/></svg>
    </button>

    <button type="button" class="mobile-view" :class="view === 'mobile' ? 'active' : ''" data-target="mobile-view" @click="setView('mobile')">
        <svg fill="none" width="14" height="19" viewBox="0 0 14 19" xmlns="http://www.w3.org/2000/svg"><rect width="12.5" height="17.5" x=".75" y=".75" stroke="#B5BAC4" stroke-width="1.5" rx="1.25"/><circle cx="7" cy="15" r="1" fill="#B5BAC4"/><path fill="#B5BAC4" d="M5 0h4v2a1 1 0 01-1 1H6a1 1 0 01-1-1V0z"/></svg>
    </button>
  </div>
</template>

<script>
export default {
    name: 'ResponsiveSwitcher',
    props: {
        setResponsiveView: {
            type: Function,
            required: true,
        },
    },
    data() {
        return {
            view: 'desktop'
        }
    },
    methods: {
        setView(view) {
            this.view = view;
            this.setResponsiveView(view);
        }
    }
}
</script>