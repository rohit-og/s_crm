<template>
    <div class="main-content">
        <breadcumb
            :page="isEditMode ? $t('Edit_Pipeline') : $t('Add_Pipeline')"
            :folder="$t('CRM')"
        />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <validation-observer
            ref="Create_Pipeline"
            v-if="!isLoading && pipeline"
        >
            <b-card>
                <b-form @submit.prevent="Submit_Pipeline">
                    <b-row>
                        <!-- Pipeline Name -->
                        <b-col md="6" sm="12">
                            <validation-provider
                                name="Name"
                                :rules="{ required: true, min: 3, max: 255 }"
                                v-slot="validationContext"
                            >
                                <b-form-group :label="$t('Name') + ' ' + '*'">
                                    <b-form-input
                                        :state="
                                            getValidationState(
                                                validationContext
                                            )
                                        "
                                        aria-describedby="name-feedback"
                                        :placeholder="$t('Enter_Pipeline_Name')"
                                        v-model="pipeline.name"
                                    ></b-form-input>
                                    <b-form-invalid-feedback
                                        id="name-feedback"
                                        >{{
                                            validationContext.errors[0]
                                        }}</b-form-invalid-feedback
                                    >
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <!-- Color -->
                        <b-col md="6" sm="12">
                            <b-form-group :label="$t('Color')">
                                <div class="d-flex align-items-center">
                                    <b-form-input
                                        v-model="pipeline.color"
                                        type="color"
                                        class="mr-2"
                                        style="width: 60px; height: 38px"
                                    ></b-form-input>
                                    <b-form-input
                                        v-model="pipeline.color"
                                        :placeholder="'#6c5ce7'"
                                    ></b-form-input>
                                </div>
                            </b-form-group>
                        </b-col>

                        <!-- Description -->
                        <b-col md="12" sm="12">
                            <b-form-group :label="$t('Description')">
                                <b-form-textarea
                                    v-model="pipeline.description"
                                    rows="3"
                                    :placeholder="
                                        $t('Enter_Pipeline_Description')
                                    "
                                ></b-form-textarea>
                            </b-form-group>
                        </b-col>

                        <!-- Sort Order -->
                        <b-col md="6" sm="12">
                            <b-form-group :label="$t('Sort_Order')">
                                <b-form-input
                                    type="number"
                                    v-model="pipeline.sort_order"
                                    :placeholder="$t('Enter_Sort_Order')"
                                    min="0"
                                ></b-form-input>
                                <small class="text-muted">{{
                                    $t("Lower_numbers_appear_first")
                                }}</small>
                            </b-form-group>
                        </b-col>

                        <!-- Is Default -->
                        <b-col md="6" sm="12">
                            <div class="psx-form-check mt-4">
                                <input
                                    type="checkbox"
                                    v-model="pipeline.is_default"
                                    class="psx-checkbox psx-form-check-input"
                                    id="is_default"
                                />
                                <label
                                    class="psx-form-check-label"
                                    for="is_default"
                                >
                                    <h5>{{ $t("Set_as_Default_Pipeline") }}</h5>
                                    <small class="text-muted d-block">{{
                                        $t("Only_one_pipeline_can_be_default")
                                    }}</small>
                                </label>
                            </div>
                        </b-col>

                        <b-col md="12" class="mt-3">
                            <b-button
                                variant="primary"
                                type="submit"
                                :disabled="SubmitProcessing"
                            >
                                {{ $t("submit") }}
                            </b-button>
                            <b-button
                                variant="secondary"
                                class="ml-2"
                                @click="$router.push({ name: 'crm-pipelines' })"
                            >
                                {{ $t("Cancel") }}
                            </b-button>
                            <div v-once class="typo__p" v-if="SubmitProcessing">
                                <div
                                    class="spinner sm spinner-primary mt-3"
                                ></div>
                            </div>
                        </b-col>
                    </b-row>
                </b-form>
            </b-card>
        </validation-observer>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";
// Use window.axios which has baseURL configured in main.js

export default {
    name: "crm-create-pipeline",
    metaInfo: {
        title: "CRM Pipeline",
    },
    data() {
        return {
            isLoading: true,
            SubmitProcessing: false,
            isEditMode: false,
            pipeline: {
                id: null,
                name: "",
                description: "",
                color: "#6c5ce7",
                is_default: false,
                sort_order: 0,
            },
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
        //------------- Submit Validation
        Submit_Pipeline() {
            this.$refs.Create_Pipeline.validate().then((success) => {
                if (!success) {
                    this.makeToast(
                        "danger",
                        this.$t("Please_fill_the_form_correctly"),
                        this.$t("Failed")
                    );
                } else {
                    if (this.isEditMode) {
                        this.Update_Pipeline();
                    } else {
                        this.Create_Pipeline();
                    }
                }
            });
        },
        //---------------------------------------- Create Pipeline
        Create_Pipeline() {
            this.SubmitProcessing = true;
            NProgress.start();
            window.axios
                .post("crm/pipelines", {
                    name: this.pipeline.name,
                    description: this.pipeline.description,
                    color: this.pipeline.color,
                    is_default: this.pipeline.is_default,
                    sort_order: parseInt(this.pipeline.sort_order) || 0,
                })
                .then((response) => {
                    this.makeToast(
                        "success",
                        this.$t("Successfully_Created"),
                        this.$t("Success")
                    );
                    this.SubmitProcessing = false;
                    NProgress.done();
                    this.$router.push({ name: "crm-pipelines" });
                })
                .catch((error) => {
                    this.makeToast(
                        "danger",
                        error.response?.data?.message || this.$t("InvalidData"),
                        this.$t("Failed")
                    );
                    this.SubmitProcessing = false;
                    NProgress.done();
                });
        },
        //---------------------------------------- Update Pipeline
        Update_Pipeline() {
            this.SubmitProcessing = true;
            NProgress.start();
            window.axios
                .put(`crm/pipelines/${this.pipeline.id}`, {
                    name: this.pipeline.name,
                    description: this.pipeline.description,
                    color: this.pipeline.color,
                    is_default: this.pipeline.is_default,
                    sort_order: parseInt(this.pipeline.sort_order) || 0,
                })
                .then((response) => {
                    this.makeToast(
                        "success",
                        this.$t("Successfully_Updated"),
                        this.$t("Success")
                    );
                    this.SubmitProcessing = false;
                    NProgress.done();
                    this.$router.push({ name: "crm-pipelines" });
                })
                .catch((error) => {
                    this.makeToast(
                        "danger",
                        error.response?.data?.message || this.$t("InvalidData"),
                        this.$t("Failed")
                    );
                    this.SubmitProcessing = false;
                    NProgress.done();
                });
        },
        //---------------------------------------- Get Pipeline Data
        Get_Pipeline(id) {
            NProgress.start();
            window.axios
                .get(`crm/pipelines/${id}`)
                .then((response) => {
                    // API returns pipeline directly or wrapped in pipeline/data key
                    const pipelineData =
                        response.data.pipeline ||
                        response.data.data ||
                        response.data;
                    // Ensure all required fields are present with defaults
                    this.pipeline = {
                        id: pipelineData.id,
                        name: pipelineData.name || "",
                        description: pipelineData.description || "",
                        color: pipelineData.color || "#6c5ce7",
                        is_default: pipelineData.is_default || false,
                        sort_order: pipelineData.sort_order || 0,
                    };
                    this.isLoading = false;
                    NProgress.done();
                })
                .catch((error) => {
                    console.error("Error fetching pipeline:", error);
                    const errorMessage =
                        error.response?.data?.message ||
                        error.response?.data?.error ||
                        error.message ||
                        this.$t("Failed");
                    this.makeToast("danger", errorMessage, this.$t("Error"));
                    this.isLoading = false;
                    NProgress.done();
                    setTimeout(() => {
                        this.$router.push({ name: "crm-pipelines" });
                    }, 1500);
                });
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
    created() {
        const id = this.$route.params.id;
        if (id && id !== "new") {
            this.isEditMode = true;
            this.Get_Pipeline(id);
        } else {
            this.isLoading = false;
        }
    },
};
</script>
