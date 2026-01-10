<template>
    <div class="main-content">
        <breadcumb
            :page="isEditMode ? $t('Edit_Form') : $t('Create_Form')"
            :folder="$t('CRM')"
        />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <div v-else>
            <b-row>
                <!-- Form Settings -->
                <b-col md="4">
                    <b-card class="mb-4">
                        <h5 class="mb-3">
                            <i class="i-Settings mr-2"></i
                            >{{ $t("Form_Settings") }}
                        </h5>
                        <validation-observer ref="Form_Settings">
                            <b-form>
                                <b-form-group :label="$t('Form_Name') + ' *'">
                                    <b-form-input
                                        v-model="form.name"
                                        :placeholder="$t('Enter_Form_Name')"
                                        required
                                    ></b-form-input>
                                </b-form-group>
                                <b-form-group :label="$t('Description')">
                                    <b-form-textarea
                                        v-model="form.description"
                                        rows="3"
                                        :placeholder="
                                            $t('Enter_Form_Description')
                                        "
                                    ></b-form-textarea>
                                </b-form-group>
                                <b-form-group :label="$t('Submit_Button_Text')">
                                    <b-form-input
                                        v-model="form.submit_button_text"
                                        placeholder="Submit"
                                    ></b-form-input>
                                </b-form-group>
                                <b-form-group :label="$t('Success_Message')">
                                    <b-form-textarea
                                        v-model="form.success_message"
                                        rows="2"
                                        placeholder="Thank you for your submission!"
                                    ></b-form-textarea>
                                </b-form-group>
                                <b-form-group :label="$t('Redirect_URL')">
                                    <b-form-input
                                        v-model="form.redirect_url"
                                        placeholder="https://example.com/thank-you"
                                    ></b-form-input>
                                </b-form-group>
                                <b-form-checkbox v-model="form.is_active">
                                    {{ $t("Active") }}
                                </b-form-checkbox>
                            </b-form>
                        </validation-observer>
                    </b-card>

                    <!-- Available Field Types -->
                    <b-card>
                        <h5 class="mb-3">
                            <i class="i-Add-Window mr-2"></i
                            >{{ $t("Available_Fields") }}
                        </h5>
                        <div class="field-types">
                            <div
                                v-for="fieldType in availableFieldTypes"
                                :key="fieldType.type"
                                class="field-type-item mb-2 p-2 border rounded cursor-pointer"
                                @click="Add_Field(fieldType)"
                            >
                                <i :class="fieldType.icon + ' mr-2'"></i>
                                <strong>{{ fieldType.label }}</strong>
                            </div>
                        </div>
                    </b-card>
                </b-col>

                <!-- Form Builder Area -->
                <b-col md="8">
                    <b-card class="mb-4">
                        <div
                            class="d-flex justify-content-between align-items-center mb-3"
                        >
                            <h5 class="mb-0">
                                <i class="i-Edit mr-2"></i
                                >{{ $t("Form_Builder") }}
                            </h5>
                            <div>
                                <b-button
                                    variant="outline-primary"
                                    size="sm"
                                    class="mr-2"
                                    @click="previewMode = !previewMode"
                                >
                                    <i class="i-Eye"></i>
                                    {{
                                        previewMode ? $t("Edit") : $t("Preview")
                                    }}
                                </b-button>
                                <b-button
                                    variant="primary"
                                    size="sm"
                                    @click="Save_Form"
                                    :disabled="SubmitProcessing"
                                >
                                    <i class="i-Disk-Save"></i>
                                    {{ $t("Save") }}
                                </b-button>
                            </div>
                        </div>

                        <!-- Preview Mode -->
                        <div
                            v-if="previewMode && formFields.length > 0"
                            class="preview-form"
                        >
                            <div
                                v-for="(field, index) in formFields"
                                :key="index"
                                class="mb-3"
                            >
                                <b-form-group
                                    :label="
                                        field.label +
                                        (field.required ? ' *' : '')
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
                                    ></b-form-input>
                                    <b-form-textarea
                                        v-else-if="field.type === 'textarea'"
                                        rows="4"
                                        :placeholder="field.placeholder"
                                        :required="field.required"
                                    ></b-form-textarea>
                                    <b-form-select
                                        v-else-if="field.type === 'select'"
                                        :options="field.options || []"
                                        :required="field.required"
                                    ></b-form-select>
                                    <div v-else-if="field.type === 'radio'">
                                        <b-form-radio
                                            v-for="(
                                                option, optIndex
                                            ) in field.options"
                                            :key="optIndex"
                                            :value="option.value"
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
                                        >
                                            {{ option.label }}
                                        </b-form-checkbox>
                                    </div>
                                    <b-form-input
                                        v-else-if="field.type === 'date'"
                                        type="date"
                                        :required="field.required"
                                    ></b-form-input>
                                    <b-form-input
                                        v-else-if="field.type === 'number'"
                                        type="number"
                                        :placeholder="field.placeholder"
                                        :required="field.required"
                                    ></b-form-input>
                                    <b-form-file
                                        v-else-if="field.type === 'file'"
                                        :required="field.required"
                                    ></b-form-file>
                                </b-form-group>
                            </div>
                            <b-button variant="primary" block>
                                {{ form.submit_button_text || "Submit" }}
                            </b-button>
                        </div>

                        <!-- Edit Mode -->
                        <div v-else>
                            <div
                                v-if="formFields.length === 0"
                                class="text-center py-5 text-muted"
                            >
                                <i class="i-Add-Window text-32 mb-3"></i>
                                <p>
                                    {{
                                        $t(
                                            "Drag_fields_from_left_or_click_to_add"
                                        )
                                    }}
                                </p>
                            </div>
                            <draggable
                                v-model="formFields"
                                @end="onFieldDragEnd"
                                handle=".drag-handle"
                                animation="200"
                            >
                                <div
                                    v-for="(field, index) in formFields"
                                    :key="field.id || index"
                                    class="field-item mb-3 p-3 border rounded"
                                >
                                    <div class="d-flex align-items-start">
                                        <div
                                            class="drag-handle mr-2 cursor-pointer"
                                        >
                                            <i
                                                class="i-Arrow-Drag text-muted"
                                                style="font-size: 20px"
                                            ></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <b-row>
                                                <b-col md="12" class="mb-2">
                                                    <b-form-group
                                                        :label="$t('Label')"
                                                    >
                                                        <b-form-input
                                                            v-model="
                                                                field.label
                                                            "
                                                            :placeholder="
                                                                $t(
                                                                    'Field_Label'
                                                                )
                                                            "
                                                        ></b-form-input>
                                                    </b-form-group>
                                                </b-col>
                                                <b-col md="6">
                                                    <b-form-group
                                                        :label="
                                                            $t('Placeholder')
                                                        "
                                                    >
                                                        <b-form-input
                                                            v-model="
                                                                field.placeholder
                                                            "
                                                            :placeholder="
                                                                $t(
                                                                    'Placeholder_Text'
                                                                )
                                                            "
                                                        ></b-form-input>
                                                    </b-form-group>
                                                </b-col>
                                                <b-col md="6">
                                                    <b-form-group>
                                                        <b-form-checkbox
                                                            v-model="
                                                                field.required
                                                            "
                                                        >
                                                            {{ $t("Required") }}
                                                        </b-form-checkbox>
                                                    </b-form-group>
                                                </b-col>
                                                <b-col
                                                    md="12"
                                                    v-if="
                                                        field.type ===
                                                            'select' ||
                                                        field.type ===
                                                            'radio' ||
                                                        field.type ===
                                                            'checkbox'
                                                    "
                                                >
                                                    <b-form-group
                                                        :label="$t('Options')"
                                                    >
                                                        <div
                                                            v-for="(
                                                                option, optIndex
                                                            ) in field.options"
                                                            :key="optIndex"
                                                            class="d-flex mb-2"
                                                        >
                                                            <b-form-input
                                                                v-model="
                                                                    option.label
                                                                "
                                                                :placeholder="
                                                                    $t(
                                                                        'Option_Label'
                                                                    )
                                                                "
                                                                class="mr-2"
                                                            ></b-form-input>
                                                            <b-form-input
                                                                v-model="
                                                                    option.value
                                                                "
                                                                :placeholder="
                                                                    $t(
                                                                        'Option_Value'
                                                                    )
                                                                "
                                                                class="mr-2"
                                                            ></b-form-input>
                                                            <b-button
                                                                variant="danger"
                                                                size="sm"
                                                                @click="
                                                                    field.options.splice(
                                                                        optIndex,
                                                                        1
                                                                    )
                                                                "
                                                            >
                                                                <i
                                                                    class="i-Close"
                                                                ></i>
                                                            </b-button>
                                                        </div>
                                                        <b-button
                                                            variant="outline-primary"
                                                            size="sm"
                                                            @click="
                                                                field.options.push(
                                                                    {
                                                                        label: '',
                                                                        value: '',
                                                                    }
                                                                )
                                                            "
                                                        >
                                                            <i
                                                                class="i-Add"
                                                            ></i>
                                                            {{
                                                                $t("Add_Option")
                                                            }}
                                                        </b-button>
                                                    </b-form-group>
                                                </b-col>
                                            </b-row>
                                        </div>
                                        <div class="ml-2">
                                            <b-button
                                                variant="danger"
                                                size="sm"
                                                @click="Remove_Field(index)"
                                            >
                                                <i class="i-Close-Window"></i>
                                            </b-button>
                                        </div>
                                    </div>
                                </div>
                            </draggable>
                        </div>
                    </b-card>
                </b-col>
            </b-row>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";
import axios from "axios";
import draggable from "vuedraggable";

export default {
    name: "crm-form-builder",
    components: {
        draggable,
    },
    metaInfo: {
        title: "Form Builder",
    },
    data() {
        return {
            isLoading: true,
            SubmitProcessing: false,
            isEditMode: false,
            previewMode: false,
            form: {
                id: null,
                name: "",
                description: "",
                submit_button_text: "Submit",
                success_message: "Thank you for your submission!",
                redirect_url: "",
                is_active: true,
                form_fields: [],
            },
            formFields: [],
            fieldIdCounter: 0,
            availableFieldTypes: [
                {
                    type: "text",
                    label: "Text",
                    icon: "i-File-Horizontal-Text",
                },
                {
                    type: "email",
                    label: "Email",
                    icon: "i-Email",
                },
                {
                    type: "phone",
                    label: "Phone",
                    icon: "i-Phone",
                },
                {
                    type: "textarea",
                    label: "Textarea",
                    icon: "i-Text-Effect",
                },
                {
                    type: "select",
                    label: "Select",
                    icon: "i-Drop-Down",
                },
                {
                    type: "radio",
                    label: "Radio",
                    icon: "i-Radio-Button",
                },
                {
                    type: "checkbox",
                    label: "Checkbox",
                    icon: "i-Check",
                },
                {
                    type: "date",
                    label: "Date",
                    icon: "i-Calendar-3",
                },
                {
                    type: "number",
                    label: "Number",
                    icon: "i-Number-Symbol",
                },
                {
                    type: "file",
                    label: "File Upload",
                    icon: "i-Upload",
                },
            ],
        };
    },
    computed: {
        ...mapGetters(["currentUserPermissions", "currentUser"]),
    },
    methods: {
        can(p) {
            return (
                this.currentUserPermissions &&
                this.currentUserPermissions.includes(p)
            );
        },
        //---------------------------------------- Add Field
        Add_Field(fieldType) {
            const newField = {
                id: `field_${this.fieldIdCounter++}_${Date.now()}`,
                type: fieldType.type,
                label: fieldType.label,
                placeholder: "",
                required: false,
                options:
                    fieldType.type === "select" ||
                    fieldType.type === "radio" ||
                    fieldType.type === "checkbox"
                        ? [
                              { label: "", value: "" },
                              { label: "", value: "" },
                          ]
                        : [],
            };
            this.formFields.push(newField);
        },
        //---------------------------------------- Remove Field
        Remove_Field(index) {
            this.formFields.splice(index, 1);
        },
        //---------------------------------------- On Field Drag End
        onFieldDragEnd() {
            // Update field order if needed
        },
        //---------------------------------------- Save Form
        async Save_Form() {
            if (!this.form.name) {
                this.makeToast(
                    "warning",
                    this.$t("Form_name_is_required"),
                    this.$t("Validation_Error")
                );
                return;
            }

            this.SubmitProcessing = true;
            NProgress.start();

            const formData = {
                name: this.form.name,
                description: this.form.description,
                submit_button_text: this.form.submit_button_text || "Submit",
                success_message: this.form.success_message,
                redirect_url: this.form.redirect_url,
                is_active: this.form.is_active,
                form_fields: this.formFields,
            };

            try {
                let response;
                if (this.isEditMode) {
                    response = await axios.put(
                        `crm/forms/${this.form.id}`,
                        formData
                    );
                } else {
                    response = await axios.post("crm/forms", formData);
                }

                this.$swal(
                    this.$t("Success"),
                    this.isEditMode
                        ? this.$t("Form_updated_successfully")
                        : this.$t("Form_created_successfully"),
                    "success"
                );

                this.SubmitProcessing = false;
                NProgress.done();

                // Redirect to forms list or stay in edit mode
                if (!this.isEditMode) {
                    this.$router.push({
                        name: "crm-form-builder",
                        params: {
                            id: response.data.form?.id || response.data.id,
                        },
                    });
                    // Reload to switch to edit mode
                    this.$router.go();
                }
            } catch (error) {
                this.makeToast(
                    "danger",
                    error.response?.data?.message ||
                        this.$t("Failed_to_save_form"),
                    this.$t("Error")
                );
                this.SubmitProcessing = false;
                NProgress.done();
            }
        },
        //---------------------------------------- Get Form Data
        async Get_Form(id) {
            NProgress.start();
            try {
                const response = await axios.get(`crm/forms/${id}`);
                this.form = response.data.form || response.data.data;
                this.formFields =
                    this.form.form_fields &&
                    typeof this.form.form_fields === "string"
                        ? JSON.parse(this.form.form_fields)
                        : this.form.form_fields || [];
                // Ensure each field has an id
                this.formFields = this.formFields.map((field, index) => ({
                    ...field,
                    id: field.id || `field_${index}_${Date.now()}`,
                }));
                this.fieldIdCounter = this.formFields.length;
                this.isLoading = false;
                NProgress.done();
            } catch (error) {
                this.makeToast(
                    "danger",
                    error.response?.data?.message || this.$t("Failed"),
                    this.$t("Error")
                );
                this.isLoading = false;
                NProgress.done();
                setTimeout(() => {
                    this.$router.push({ name: "crm-forms" });
                }, 1500);
            }
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
    created() {
        const id = this.$route.params.id;
        if (id && id !== "new") {
            this.isEditMode = true;
            this.Get_Form(id);
        } else {
            this.isLoading = false;
        }
    },
};
</script>

<style scoped>
.field-type-item {
    transition: all 0.2s ease;
}

.field-type-item:hover {
    background-color: #f8f9fa;
    border-color: #007bff !important;
}

.field-item {
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.field-item:hover {
    background-color: #e9ecef;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.drag-handle {
    cursor: move;
}

.cursor-pointer {
    cursor: pointer;
}

.preview-form {
    background-color: #fff;
    padding: 20px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}
</style>
