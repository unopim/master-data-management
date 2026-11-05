<v-code-pattern-preview
    type-code="{{ $typeCode ?? '' }}"
    pattern="{{ $pattern ?? '' }}"
    :start="{{ (int) ($start ?? 1) }}"
></v-code-pattern-preview>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-code-pattern-preview-template"
    >
        <div class="mt-2">
            <template v-if="examples.length">
                <p class="mb-1 text-xs text-gray-600 dark:text-gray-300">
                    @lang('masterdata::app.entity-types.form.code-pattern-preview')
                </p>

                <div class="flex flex-wrap gap-1.5">
                    <x-admin::badge
                        class="font-mono"
                        v-for="example in examples"
                        ::key="example"
                    >
                        @{{ example }}
                    </x-admin::badge>
                </div>
            </template>

            <p
                class="mt-1 text-xs text-danger"
                v-else-if="currentPattern"
            >
                @lang('masterdata::app.entity-types.form.code-pattern-missing-seq')
            </p>
        </div>
    </script>

    <script type="module">
        app.component('v-code-pattern-preview', {
            template: '#v-code-pattern-preview-template',

            props: {
                typeCode: {
                    type: String,
                    default: '',
                },

                pattern: {
                    type: String,
                    default: '',
                },

                start: {
                    type: Number,
                    default: 1,
                },
            },

            data() {
                return {
                    currentTypeCode: this.typeCode,

                    currentPattern: this.pattern,

                    form: null,
                };
            },

            computed: {
                examples() {
                    const sequence = /\{seq(?::(\d{1,2}))?\}/g;

                    if (! this.currentPattern || ! this.currentPattern.match(sequence)) {
                        return [];
                    }

                    const type = this.$sanitizeCode(this.currentTypeCode).toUpperCase() || 'TYPE';

                    return [0, 1, 2].map((offset) => this.currentPattern
                        .replaceAll('{TYPE}', type)
                        .replace(sequence, (match, width) => String(this.start + offset).padStart(Number(width ?? 0), '0')));
                },
            },

            mounted() {
                this.form = this.$el.closest('form');

                this.read();

                this.form?.addEventListener('input', this.read);
            },

            beforeUnmount() {
                this.form?.removeEventListener('input', this.read);
            },

            methods: {
                read() {
                    const code = this.form?.querySelector('[name="code"]');
                    const pattern = this.form?.querySelector('[name="code_pattern"]');

                    if (code) {
                        this.currentTypeCode = code.value;
                    }

                    if (pattern) {
                        this.currentPattern = pattern.value;
                    }
                },
            },
        });
    </script>
@endPushOnce
