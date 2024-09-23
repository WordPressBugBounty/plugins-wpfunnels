<template>
    <div class="category-filter-wrapper">
        <ul>
            <li @click="doCatFilter('all')" :class="'all' === activeCategory ? 'active' : ''" >
                <CheckWithCircle />
                All
            </li>
            <li v-for="(data, index) in categories" :class="data.slug === activeCategory ? 'active' : ''" :data-value="data.slug" :key="index" @click="doCatFilter(data.slug)">
                <BeautyIcon v-if="'beauty' === data.slug" />
                <BusinessCoachIcon v-if="'business-coach' === data.slug" />
                <CounselorIcon v-if="'counselor' === data.slug" />
                <DigitalMarketer v-if="'digital-marketer' === data.slug" />
                <EbookIcon v-if="'ebook' === data.slug" />
                <EcommerceShopIcon v-if="'ecommerce-shop' === data.slug" />
                <EducationalPortalIcon v-if="'educational-portal' === data.slug" />
                <EmailIcon v-if="'email' === data.slug" />
                <EvergreenTemplateIcon v-if="'evergreen-template' === data.slug" />
                <HealthSupplementIcon v-if="'health-supplement-provider' === data.slug" />
                <LegalAdvisorIcon v-if="'legal-advisor' === data.slug" />
                <LifeCoachIcon v-if="'life-coach' === data.slug" />
                <MarketingTutorIcon v-if="'marketing-tutor' === data.slug" />
                <OnlineCourseIcon v-if="'online-course' === data.slug" />
                <OnlineFitnessTrainerIcon v-if="'online-fitness-trainer' === data.slug" />
                <PetCareIcon v-if="'pet-care' === data.slug" />
                <RealEstateIcon v-if="'real-estate' === data.slug" />
                <SaasProductIcon v-if="'sass-product' === data.slug" />
                <SoftwareFirmIcon v-if="'software-firm' === data.slug" />
                <WebinarIcon v-if="'webniar' === data.slug" />
                <WeightLossIcon v-if="'weight-loss' === data.slug" />
                <YogaInstructorIcon v-if="'yoga-instructor' === data.slug" />
                {{ data.name }}
            </li>
        </ul>
    </div>
</template>

<script>
var j = jQuery.noConflict()
import CheckWithCircle from '../../../src/components/icons/industries-icon/CheckWithCircle.vue'
import BeautyIcon from '../../../src/components/icons/industries-icon/BeautyIcon.vue'
import BusinessCoachIcon from '../../../src/components/icons/industries-icon/BusinessCoachIcon.vue'
import CounselorIcon from '../../../src/components/icons/industries-icon/CounselorIcon.vue'
import DigitalMarketer from '../../../src/components/icons/industries-icon/DigitalMarketer.vue'
import EbookIcon from '../../../src/components/icons/industries-icon/EbookIcon.vue'
import EcommerceShopIcon from '../../../src/components/icons/industries-icon/EcommerceShopIcon.vue'
import EducationalPortalIcon from '../../../src/components/icons/industries-icon/EducationalPortalIcon.vue'
import EmailIcon from '../../../src/components/icons/industries-icon/EmailIcon.vue'
import EvergreenTemplateIcon from '../../../src/components/icons/industries-icon/EvergreenTemplateIcon.vue'
import HealthSupplementIcon from '../../../src/components/icons/industries-icon/HealthSupplementIcon.vue'
import LegalAdvisorIcon from '../../../src/components/icons/industries-icon/LegalAdvisorIcon.vue'
import LifeCoachIcon from '../../../src/components/icons/industries-icon/LifeCoachIcon.vue'
import MarketingTutorIcon from '../../../src/components/icons/industries-icon/MarketingTutorIcon.vue'
import OnlineCourseIcon from '../../../src/components/icons/industries-icon/OnlineCourseIcon.vue'
import OnlineFitnessTrainerIcon from '../../../src/components/icons/industries-icon/OnlineFitnessTrainerIcon.vue'
import PetCareIcon from '../../../src/components/icons/industries-icon/PetCareIcon.vue'
import RealEstateIcon from '../../../src/components/icons/industries-icon/RealEstateIcon.vue'
import SaasProductIcon from '../../../src/components/icons/industries-icon/SaasProductIcon.vue'
import SoftwareFirmIcon from '../../../src/components/icons/industries-icon/SoftwareFirmIcon.vue'
import WebinarIcon from '../../../src/components/icons/industries-icon/WebinarIcon.vue'
import WeightLossIcon from '../../../src/components/icons/industries-icon/WeightLossIcon.vue'
import YogaInstructorIcon from '../../../src/components/icons/industries-icon/YogaInstructorIcon.vue'

export default {
    name: 'CategoryNav',
    components: {
        CheckWithCircle,
        BeautyIcon,
        BusinessCoachIcon,
        CounselorIcon,
        DigitalMarketer,
        EbookIcon,
        EcommerceShopIcon,
        EducationalPortalIcon,
        EmailIcon,
        EvergreenTemplateIcon,
        HealthSupplementIcon,
        LegalAdvisorIcon,
        LifeCoachIcon,
        MarketingTutorIcon,
        OnlineCourseIcon,
        OnlineFitnessTrainerIcon,
        PetCareIcon,
        RealEstateIcon,
        SaasProductIcon,
        SoftwareFirmIcon,
        WebinarIcon,
        WeightLossIcon,
        YogaInstructorIcon,
    },
    props: {
        categories: Array,
        activeCategory: String,
        
    },
    mounted: function() {
        var vm = this;
        j(this.$el.getElementsByTagName('select'))
            .select2({ 
                data: this.categories,
                //dropdownParent: j('.category-select-dropdown')
            })
            .val(this.value)
            .trigger("change")
            .on("change", function() {
                vm.$emit("doCatFilter", this.value);
                vm.$emit("doStepCatFilter", this.value);
            });

        j(document).on('select2:open', function() {
            document.querySelector('.select2-search__field').focus();
        });
    },
    data: function () {
        return {
            activeClass: '',
            activeCat: 'all'
        }
    },
    methods: {
        doCatFilter: function (slug) {
            
            this.$emit('doCatFilter', slug)
        }
    }

}
</script>