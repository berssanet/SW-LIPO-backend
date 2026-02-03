<template>
    <SfLoader :loading="isPaidOrder">
        <template #loader>
            <div class="sf-loader">
                <transition name="sf-fade" mode="out-in">
                    <div class="sf-loader__overlay">
                        <SfHeading
                            :level="3"
                            :title="$t('waiting for order payment.')"
                            :description="$t('Please wait...')"
                        />
                        <svg
                            class="sf-loader__spinner"
                            role="img"
                            width="38"
                            height="38"
                            viewBox="0 0 38 38"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <g fill="none" fill-rule="evenodd">
                                <g transform="translate(1 1)" stroke-width="2">
                                    <circle stroke-opacity=".5" cx="18" cy="18" r="18" />
                                    <path d="M36 18c0-9.94-8.06-18-18-18">
                                        <animateTransform
                                            attributeName="transform"
                                            type="rotate"
                                            from="0 18 18"
                                            to="360 18 18"
                                            dur="1s"
                                            repeatCount="indefinite"
                                        />
                                    </path>
                                </g>
                            </g>
                        </svg>
                    </div>
                </transition>
            </div>
        </template>
    </SfLoader>
</template>

<script>
import { onMounted, computed, ref } from "@vue/composition-api"
import { SfHeading, SfLoader } from "@storefront-ui/vue"

export default {
    name: "datatransPaymentPage",
    head: {
        script: [
            {
                src: "https://pay.sandbox.datatrans.com/upp/payment/js/datatrans-2.0.0.js",
            },
        ],
    },
    components: {
        SfLoader,
        SfHeading
    },
    setup(props, { root }) {
        const isPaidOrder = ref(true);
        const datatransTrxId = computed(() => root.$route.query.datatransTrxId);

        onMounted(async () =>{
            Datatrans.startPayment({
                transactionId:  datatransTrxId.value,
                'opened': function() {console.log('payment-form opened');},
                'loaded': function() {console.log('payment-form loaded');},
                'closed': function() {console.log('payment-page closed');},
                'error': function() {console.log('error');}
            });
        })

        return{
            datatransTrxId,
            isPaidOrder
        }
    }
}
</script>

<style scoped>

</style>
