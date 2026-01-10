<template>
    <div class="form-renderer">
        <div v-if="form">
            <div class="mb-4" v-if="form.description">
                <p class="text-muted">{{ form.description }}</p>
            </div>
            <validation-observer ref="Form_Submit">
                <b-form @submit.prevent="Submit_Form">
                    <div
                        v-for="(field, index) in formFields"
                        :key="index"
                        class="mb-3"
                    >
                        <validation-provider
                            :name="field.label"
                            :rules="field.required ? { required: true } : {}"
                            v-slot="validationContext"
                        >
                            <b-form-group
                                :label="
                                    field.label + (field.required ? ' *' : '')
                                "
                            >
                                <b-form-input
                                    v-if="
                                        field.type === 'text' ||
                                        field.type === 'email' ||
                                        field.type === 'phone'
                                    "
                                    :type="
                                        field.type === 'email'
                                            ? 'email'
                                            : field.type === 'phone'
                                            ? 'tel'
                                            : 'text'
                                    "
                                    :placeholder="field.placeholder"
                                    :required="field.required"
                                    :state="
                                        getValidationState(validationContext)
                                    "
                                    v-model="formData[field.id]"
                                ></b-form-input>
                                <b-form-textarea
                                    v-else-if="field.type === 'textarea'"
                                    rows="4"
                                    :placeholder="field.placeholder"
                                    :required="field.required"
                                    :state="
                                        getValidationState(validationContext)
                                    "
                                    v-model="formData[field.id]"
                                ></b-form-textarea>
                                <b-form-select
                                    v-else-if="field.type === 'select'"
                                    :options="formatOptions(field.options)"
                                    :required="field.required"
                                    :state="
                                        getValidationState(validationContext)
                                    "
                                    v-model="formData[field.id]"
                                ></b-form-select>
                                <div v-else-if="field.type === 'radio'">
                                    <b-form-radio
                                        v-for="(
                                            option, optIndex
                                        ) in field.options"
                                        :key="optIndex"
                                        :value="option.value"
                                        :state="
                                            getValidationState(
                                                validationContext
                                            )
                                        "
                                        v-model="formData[field.id]"
                                    >
                                        {{ option.label }}
                                    </b-form-radio>
                                </div>
                                <div v-else-if="field.type === 'checkbox'">
                                    <b-form-checkbox
                                        v-for="(
                                            option, optIndex
                                        ) in field.options"
                                        :key="optIndex"
                                        :value="option.value"
                                        v-model="formData[field.id]"
                                    >
                                        {{ option.label }}
                                    </b-form-checkbox>
                                </div>
                                <b-form-input
                                    v-else-if="field.type === 'date'"
                                    type="date"
                                    :required="field.required"
                                    :state="
                                        getValidationState(validationContext)
                                    "
                                    v-model="formData[field.id]"
                                ></b-form-input>
                                <b-form-input
                                    v-else-if="field.type === 'number'"
                                    type="number"
                                    :placeholder="field.placeholder"
                                    :required="field.required"
                                    :state="
                                        getValidationState(validationContext)
                                    "
                                    v-model.number="formData[field.id]"
                                ></b-form-input>
                                <b-form-file
                                    v-else-if="field.type === 'file'"
                                    :required="field.required"
                                    :state="
                                        getValidationState(validationContext)
                                    "
                                    v-model="formData[field.id]"
                                ></b-form-file>
                                <b-form-invalid-feedback
                                    v-if="validationContext.errors[0]"
                                >
                                    {{ validationContext.errors[0] }}
                                </b-form-invalid-feedback>
                            </b-form-group>
                        </validation-provider>
                    </div>
                    <b-button
                        type="submit"
                        variant="primary"
                        size="lg"
                        block
                        :disabled="SubmitProcessing"
                    >
                        <span
                            v-if="SubmitProcessing"
                            class="spinner spinner-sm mr-2"
                        ></span>
                        {{ form.submit_button_text || "Submit" }}
                    </b-button>
                </b-form>
            </validation-observer>
        </div>
        <div v-else-if="!isLoading" class="alert alert-warning">
            {{ $t("Form_not_found") }}
        </div>
    </div>
</template>

<script>
import NProgress from "nprogress";
import axios from "axios";

export default {
    name: "crm-form-renderer",
    props: {
        formId: {
            type: [String, Number],
            required: true,
        },
    },
    data() {
        return {
            isLoading: true,
            SubmitProcessing: false,
            form: null,
            formFields: [],
            formData: {},
        };
    },
    methods: {
        //---------------------------------------- Get Form Data
        async Get_Form() {
            NProgress.start();
            try {
                const response = await axios.get(`crm/forms/${this.formId}`);
                this.form = response.data.form || response.data.data;
                this.formFields =
                    this.form.form_fields &&
                    typeof this.form.form_fields === "string"
                        ? JSON.parse(this.form.form_fields)
                        : this.form.form_fields || [];
                // Initialize form data with field ids
                this.formFields.forEach((field) => {
                    if (!this.formData[field.id]) {
                        if (field.type === "checkbox") {
                            this.formData[field.id] = [];
                        } else {
                            this.formData[field.id] = "";
                        }
                    }
                });
                this.isLoading = false;
                NProgress.done();
            } catch (error) {
                this.$swal(
                    this.$t("Error"),
                    error.response?.data?.message ||
                        this.$t("Failed_to_load_form"),
                    "error"
                );
                this.isLoading = false;
                NProgress.done();
            }
        },
        //---------------------------------------- Format Options
        formatOptions(options) {
            if (!options || !Array.isArray(options)) return [];
            return options.map((opt) => ({
                value: opt.value,
                text: opt.label || opt.value,
            }));
        },
        //---------------------------------------- Submit Form
        async Submit_Form() {
            this.$refs.Form_Submit.validate().then((success) => {
                if (!success) {
                    this.makeToast(
                        "danger",
                        this.$t("Please_fill_all_required_fields"),
                        this.$t("Validation_Error")
                    );
                } else {
                    this.Submit_Form_Data();
                }
            });
        },
        //---------------------------------------- Submit Form Data
        async Submit_Form_Data() {
            this.SubmitProcessing = true;
            NProgress.start();

            const formDataToSend = new FormData();
            formDataToSend.append("form_id", this.formId);
            formDataToSend.append("data", JSON.stringify(this.formData));

            // Handle file uploads
            this.formFields.forEach((field) => {
                if (field.type === "file" && this.formData[field.id]) {
                    if (Array.isArray(this.formData[field.id])) {
                        this.formData[field.id].forEach((file) => {
                            formDataToSend.append(`files[${field.id}][]`, file);
                        });
                    } else {
                        formDataToSend.append(
                            `files[${field.id}]`,
                            this.formData[field.id]
                        );
                    }
                }
            });

            try {
                const response = await axios.post(
                    `crm/forms/${this.formId}/submit`,
                    formDataToSend,
                    {
                        headers: {
                            "Content-Type": "multipart/form-data",
                        },
                    }
                );

                this.$swal(
                    this.$t("Success"),
                    this.form.success_message ||
                        this.$t("Thank_you_for_your_submission"),
                    "success"
                );

                // Reset form
                this.formData = {};
                this.formFields.forEach((field) => {
                    if (field.type === "checkbox") {
                        this.formData[field.id] = [];
                    } else {
                        this.formData[field.id] = "";
                    }
                });

                // Redirect if URL is provided
                if (this.form.redirect_url) {
                    setTimeout(() => {
                        window.location.href = this.form.redirect_url;
                    }, 2000);
                }
            } catch (error) {
                this.$swal(
                    this.$t("Error"),
                    error.response?.data?.message ||
                        this.$t("Failed_to_submit_form"),
                    "error"
                );
            } finally {
                this.SubmitProcessing = false;
                NProgress.done();
            }
        },
        //------ Validation State
        getValidationState({ dirty, validated, valid = null }) {
            return dirty || validated ? valid : null;
        },
        //------ Toast
        makeToast(variant, msg, title) {
            this.$root.$bvToast.toast(msg, {
                title: title,
                variant: variant,
                solid: true,
            });
        },
    },
    async created() {
        await this.Get_Form();
    },
};
</script>

<style scoped>
.form-renderer {
    max-width: 800px;
    margin: 0 auto;
}
</style>
