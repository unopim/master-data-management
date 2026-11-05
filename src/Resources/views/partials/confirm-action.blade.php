@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-masterdata-confirm-action-template"
    >
        <button
            type="button"
            :class="buttonClass"
            :disabled="isSubmitting"
            @click="confirm"
        >
            @{{ label }}
        </button>
    </script>

    <script type="module">
        app.component('v-masterdata-confirm-action', {
            template: '#v-masterdata-confirm-action-template',

            props: {
                url: { type: String, required: true },
                method: { type: String, default: 'post' },
                payload: { type: Object, default: () => ({}) },
                label: { type: String, required: true },
                title: { type: String, required: true },
                message: { type: String, default: '' },
                buttonClass: { type: String, default: 'secondary-button' },
            },

            data() {
                return {
                    isSubmitting: false,
                };
            },

            methods: {
                confirm() {
                    this.$emitter.emit('open-confirm-modal', {
                        title: this.title,
                        message: this.message,
                        agree: () => this.submit(),
                    });
                },

                submit() {
                    this.isSubmitting = true;

                    const request = this.method === 'delete'
                        ? this.$axios.delete(this.url, { data: this.payload })
                        : this.$axios.post(this.url, this.payload);

                    request
                        .then((response) => this.$navigate(response.data.redirect_url))
                        .catch((error) => {
                            this.isSubmitting = false;

                            const errors = error.response?.data?.errors;

                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: errors ? Object.values(errors).flat()[0] : error.response?.data?.message,
                            });
                        });
                },
            },
        });
    </script>
@endPushOnce
