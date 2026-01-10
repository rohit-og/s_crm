<template>
    <div class="main-content">
        <breadcumb
            :page="isEditMode ? $t('Edit_Deal') : $t('Add_Deal')"
            :folder="$t('CRM')"
        />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <validation-observer ref="Create_Deal" v-if="!isLoading">
            <b-card>
                <b-form @submit.prevent="Submit_Deal">
                    <b-row>
                        <!-- Deal Name -->
                        <b-col md="6" sm="12">
                            <validation-provider
                                name="Name"
                                :rules="{ required: true, min: 3 }"
                                v-slot="validationContext"
                            >
                                <b-form-group
                                    :label="$t('Deal_Name') + ' ' + '*'"
                                >
                                    <b-form-input
                                        :state="
                                            getValidationState(
                                                validationContext
                                            )
                                        "
                                        aria-describedby="name-feedback"
                                        :placeholder="$t('Enter_Deal_Name')"
                                        v-model="deal.name"
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

                        <!-- Client -->
                        <b-col md="6" sm="12">
                            <validation-provider
                                name="Client"
                                :rules="{ required: true }"
                                v-slot="validationContext"
                            >
                                <b-form-group :label="$t('Client') + ' ' + '*'">
                                    <v-select
                                        :class="{
                                            'is-invalid':
                                                !!validationContext.errors
                                                    .length,
                                        }"
                                        :state="
                                            validationContext.errors[0]
                                                ? false
                                                : validationContext.valid
                                                ? true
                                                : null
                                        "
                                        :options="clients"
                                        label="name"
                                        :reduce="(option) => option.id"
                                        :placeholder="$t('Select_Client')"
                                        v-model="deal.client_id"
                                        @input="onClientChange"
                                    />
                                    <b-form-invalid-feedback>{{
                                        validationContext.errors[0]
                                    }}</b-form-invalid-feedback>
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <!-- Pipeline -->
                        <b-col md="6" sm="12">
                            <validation-provider
                                name="Pipeline"
                                :rules="{ required: true }"
                                v-slot="validationContext"
                            >
                                <b-form-group
                                    :label="$t('Pipeline') + ' ' + '*'"
                                >
                                    <v-select
                                        :class="{
                                            'is-invalid':
                                                !!validationContext.errors
                                                    .length,
                                        }"
                                        :state="
                                            validationContext.errors[0]
                                                ? false
                                                : validationContext.valid
                                                ? true
                                                : null
                                        "
                                        :options="pipelines"
                                        label="name"
                                        :reduce="(option) => option.id"
                                        :placeholder="$t('Select_Pipeline')"
                                        v-model="deal.pipeline_id"
                                        @input="onPipelineChange"
                                    />
                                    <b-form-invalid-feedback>{{
                                        validationContext.errors[0]
                                    }}</b-form-invalid-feedback>
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <!-- Pipeline Stage -->
                        <b-col md="6" sm="12">
                            <validation-provider
                                name="Stage"
                                :rules="{ required: true }"
                                v-slot="validationContext"
                            >
                                <b-form-group :label="$t('Stage') + ' ' + '*'">
                                    <v-select
                                        :class="{
                                            'is-invalid':
                                                !!validationContext.errors
                                                    .length,
                                        }"
                                        :state="
                                            validationContext.errors[0]
                                                ? false
                                                : validationContext.valid
                                                ? true
                                                : null
                                        "
                                        :options="stages"
                                        label="name"
                                        :reduce="(option) => option.id"
                                        :placeholder="$t('Select_Stage')"
                                        v-model="deal.pipeline_stage_id"
                                        :disabled="!deal.pipeline_id"
                                    />
                                    <b-form-invalid-feedback>{{
                                        validationContext.errors[0]
                                    }}</b-form-invalid-feedback>
                                    <small
                                        v-if="!deal.pipeline_id"
                                        class="text-muted"
                                        >{{
                                            $t("Please_select_pipeline_first")
                                        }}</small
                                    >
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <!-- Value -->
                        <b-col md="6" sm="12">
                            <b-form-group :label="$t('Deal_Value')">
                                <b-input-group>
                                    <b-input-group-prepend>
                                        <b-input-group-text>{{
                                            currentUser?.currency || "$"
                                        }}</b-input-group-text>
                                    </b-input-group-prepend>
                                    <b-form-input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        v-model="deal.value"
                                        :placeholder="$t('Enter_Deal_Value')"
                                    ></b-form-input>
                                </b-input-group>
                            </b-form-group>
                        </b-col>

                        <!-- Currency -->
                        <b-col md="6" sm="12">
                            <b-form-group :label="$t('Currency')">
                                <b-form-input
                                    v-model="deal.currency"
                                    :placeholder="
                                        currentUser?.currency || 'USD'
                                    "
                                ></b-form-input>
                            </b-form-group>
                        </b-col>

                        <!-- Expected Close Date -->
                        <b-col md="6" sm="12">
                            <b-form-group :label="$t('Expected_Close_Date')">
                                <b-form-input
                                    type="date"
                                    v-model="deal.expected_close_date"
                                ></b-form-input>
                            </b-form-group>
                        </b-col>

                        <!-- Probability -->
                        <b-col md="6" sm="12">
                            <b-form-group :label="$t('Probability') + ' (%)'">
                                <b-form-input
                                    type="range"
                                    min="0"
                                    max="100"
                                    v-model="deal.probability"
                                    class="mb-2"
                                ></b-form-input>
                                <div class="d-flex justify-content-between">
                                    <small>0%</small>
                                    <strong
                                        >{{ deal.probability || 0 }}%</strong
                                    >
                                    <small>100%</small>
                                </div>
                            </b-form-group>
                        </b-col>

                        <!-- Assigned To -->
                        <b-col md="6" sm="12">
                            <b-form-group :label="$t('Assigned_To')">
                                <v-select
                                    :options="agents"
                                    label="name"
                                    :reduce="(option) => option.id"
                                    :placeholder="$t('Select_Agent')"
                                    v-model="deal.assigned_to"
                                />
                                <small class="text-muted">{{
                                    $t("Leave_empty_to_assign_to_yourself")
                                }}</small>
                            </b-form-group>
                        </b-col>

                        <!-- Status -->
                        <b-col md="6" sm="12">
                            <b-form-group :label="$t('Status')">
                                <v-select
                                    :options="statusOptions"
                                    :placeholder="$t('Select_Status')"
                                    v-model="deal.status"
                                />
                            </b-form-group>
                        </b-col>

                        <!-- Description -->
                        <b-col md="12" sm="12">
                            <b-form-group :label="$t('Description')">
                                <b-form-textarea
                                    v-model="deal.description"
                                    rows="4"
                                    :placeholder="$t('Enter_Deal_Description')"
                                ></b-form-textarea>
                            </b-form-group>
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
                                @click="$router.push({ name: 'crm-deals' })"
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
    name: "crm-create-deal",
    metaInfo: {
        title: "CRM Deal",
    },
    data() {
        return {
            isLoading: true,
            SubmitProcessing: false,
            isEditMode: false,
            deal: {
                id: null,
                name: "",
                description: "",
                client_id: null,
                pipeline_id: null,
                pipeline_stage_id: null,
                value: 0,
                currency: "",
                expected_close_date: null,
                actual_close_date: null,
                probability: 50,
                status: "open",
                assigned_to: null,
            },
            clients: [],
            pipelines: [],
            stages: [],
            agents: [],
            statusOptions: [
                { label: "Open", value: "open" },
                { label: "Closed Won", value: "closed_won" },
                { label: "Closed Lost", value: "closed_lost" },
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
        //---------------------------------------- On Pipeline Change
        async onPipelineChange(pipelineId) {
            if (pipelineId) {
                this.deal.pipeline_stage_id = null;
                await this.Fetch_Pipeline_Stages(pipelineId);
            } else {
                this.stages = [];
            }
        },
        //---------------------------------------- On Client Change
        onClientChange() {
            // Can add additional logic here if needed
        },
        //---------------------------------------- Fetch Clients
        async Fetch_Clients() {
            try {
                const response = await window.axios.get("clients", {
                    params: { limit: -1 },
                });
                this.clients =
                    response.data.clients || response.data.data || [];
            } catch (error) {
                console.error("Error fetching clients:", error);
            }
        },
        //---------------------------------------- Fetch Pipelines
        async Fetch_Pipelines() {
            try {
                const response = await window.axios.get("crm/pipelines", {
                    params: { limit: -1 },
                });
                this.pipelines =
                    response.data.pipelines || response.data.data || [];
            } catch (error) {
                console.error("Error fetching pipelines:", error);
            }
        },
        //---------------------------------------- Fetch Pipeline Stages
        async Fetch_Pipeline_Stages(pipelineId) {
            try {
                const response = await window.axios.get(
                    `crm/pipelines/${pipelineId}/stages`
                );
                this.stages = response.data.stages || response.data.data || [];
                // If editing and no stage selected, select first stage
                if (
                    this.stages.length > 0 &&
                    !this.deal.pipeline_stage_id &&
                    !this.isEditMode
                ) {
                    const defaultStage = this.stages.find(
                        (s) => s.is_default_stage
                    );
                    if (defaultStage) {
                        this.deal.pipeline_stage_id = defaultStage.id;
                    }
                }
            } catch (error) {
                console.error("Error fetching stages:", error);
                this.stages = [];
            }
        },
        //---------------------------------------- Fetch Agents
        async Fetch_Agents() {
            try {
                const response = await window.axios.get("users", {
                    params: { role: "crm_agent", limit: -1 },
                });
                this.agents = response.data.users || response.data.data || [];
            } catch (error) {
                console.error("Error fetching agents:", error);
            }
        },
        //------------- Submit Validation
        Submit_Deal() {
            this.$refs.Create_Deal.validate().then((success) => {
                if (!success) {
                    this.makeToast(
                        "danger",
                        this.$t("Please_fill_the_form_correctly"),
                        this.$t("Failed")
                    );
                } else {
                    if (this.isEditMode) {
                        this.Update_Deal();
                    } else {
                        this.Create_Deal();
                    }
                }
            });
        },
        //---------------------------------------- Create Deal
        Create_Deal() {
            this.SubmitProcessing = true;
            NProgress.start();
            const data = {
                name: this.deal.name,
                description: this.deal.description,
                client_id: this.deal.client_id,
                pipeline_id: this.deal.pipeline_id,
                pipeline_stage_id: this.deal.pipeline_stage_id,
                value: parseFloat(this.deal.value) || 0,
                currency:
                    this.deal.currency || this.currentUser?.currency || "USD",
                expected_close_date: this.deal.expected_close_date,
                probability: parseInt(this.deal.probability) || 0,
                status: this.deal.status || "open",
                assigned_to: this.deal.assigned_to,
            };

            window.axios
                .post("crm/deals", data)
                .then((response) => {
                    this.makeToast(
                        "success",
                        this.$t("Successfully_Created"),
                        this.$t("Success")
                    );
                    this.SubmitProcessing = false;
                    NProgress.done();
                    this.$router.push({
                        name: "crm-deal-detail",
                        params: {
                            id: response.data.deal?.id || response.data.id,
                        },
                    });
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
        //---------------------------------------- Update Deal
        Update_Deal() {
            this.SubmitProcessing = true;
            NProgress.start();
            const data = {
                name: this.deal.name,
                description: this.deal.description,
                client_id: this.deal.client_id,
                pipeline_id: this.deal.pipeline_id,
                pipeline_stage_id: this.deal.pipeline_stage_id,
                value: parseFloat(this.deal.value) || 0,
                currency:
                    this.deal.currency || this.currentUser?.currency || "USD",
                expected_close_date: this.deal.expected_close_date,
                actual_close_date: this.deal.actual_close_date,
                probability: parseInt(this.deal.probability) || 0,
                status: this.deal.status || "open",
                assigned_to: this.deal.assigned_to,
            };

            window.axios
                .put(`crm/deals/${this.deal.id}`, data)
                .then(() => {
                    this.makeToast(
                        "success",
                        this.$t("Successfully_Updated"),
                        this.$t("Success")
                    );
                    this.SubmitProcessing = false;
                    NProgress.done();
                    this.$router.push({
                        name: "crm-deal-detail",
                        params: { id: this.deal.id },
                    });
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
        //---------------------------------------- Get Deal Data
        async Get_Deal(id) {
            NProgress.start();
            try {
                const response = await window.axios.get(`crm/deals/${id}`);
                this.deal = response.data.deal || response.data.data;
                this.deal.currency =
                    this.deal.currency || this.currentUser?.currency || "USD";
                if (this.deal.pipeline_id) {
                    await this.Fetch_Pipeline_Stages(this.deal.pipeline_id);
                }
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
                    this.$router.push({ name: "crm-deals" });
                }, 1500);
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
        await Promise.all([
            this.Fetch_Clients(),
            this.Fetch_Pipelines(),
            this.Fetch_Agents(),
        ]);

        const id = this.$route.params.id;
        if (id && id !== "new") {
            this.isEditMode = true;
            await this.Get_Deal(id);
        } else {
            this.deal.currency = this.currentUser?.currency || "USD";
            this.deal.status = "open";
            this.isLoading = false;
        }
    },
};
</script>
