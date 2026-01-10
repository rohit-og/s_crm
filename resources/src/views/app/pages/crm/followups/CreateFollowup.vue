<template>
    <div class="main-content">
        <breadcumb
            :page="isEditMode ? $t('Edit_Followup') : $t('Add_Followup')"
            :folder="$t('CRM')"
        />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <validation-observer ref="Create_Followup" v-if="!isLoading">
            <b-card>
                <b-form @submit.prevent="Submit_Followup">
                    <b-row>
                        <!-- Type -->
                        <b-col md="6" sm="12">
                            <validation-provider
                                name="Type"
                                :rules="{ required: true }"
                                v-slot="validationContext"
                            >
                                <b-form-group :label="$t('Type') + ' ' + '*'">
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
                                        :options="typeOptions"
                                        :placeholder="$t('Select_Type')"
                                        v-model="followup.type"
                                        @input="onTypeChange"
                                    />
                                    <b-form-invalid-feedback>{{
                                        validationContext.errors[0]
                                    }}</b-form-invalid-feedback>
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <!-- Subject -->
                        <b-col md="6" sm="12">
                            <validation-provider
                                name="Subject"
                                :rules="{ required: true, min: 3 }"
                                v-slot="validationContext"
                            >
                                <b-form-group
                                    :label="$t('Subject') + ' ' + '*'"
                                >
                                    <b-form-input
                                        :state="
                                            getValidationState(
                                                validationContext
                                            )
                                        "
                                        aria-describedby="subject-feedback"
                                        :placeholder="$t('Enter_Subject')"
                                        v-model="followup.subject"
                                    ></b-form-input>
                                    <b-form-invalid-feedback
                                        id="subject-feedback"
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
                                        v-model="followup.client_id"
                                    />
                                    <b-form-invalid-feedback>{{
                                        validationContext.errors[0]
                                    }}</b-form-invalid-feedback>
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <!-- Deal (Optional) -->
                        <b-col md="6" sm="12">
                            <b-form-group
                                :label="
                                    $t('Deal') + ' (' + $t('Optional') + ')'
                                "
                            >
                                <v-select
                                    :options="deals"
                                    label="name"
                                    :reduce="(option) => option.id"
                                    :placeholder="$t('Select_Deal')"
                                    v-model="followup.deal_id"
                                />
                            </b-form-group>
                        </b-col>

                        <!-- Scheduled At -->
                        <b-col md="6" sm="12">
                            <validation-provider
                                name="Scheduled_At"
                                :rules="{ required: true }"
                                v-slot="validationContext"
                            >
                                <b-form-group
                                    :label="$t('Scheduled_At') + ' ' + '*'"
                                >
                                    <b-form-input
                                        type="datetime-local"
                                        :state="
                                            getValidationState(
                                                validationContext
                                            )
                                        "
                                        aria-describedby="scheduled-feedback"
                                        v-model="followup.scheduled_at"
                                    ></b-form-input>
                                    <b-form-invalid-feedback
                                        id="scheduled-feedback"
                                        >{{
                                            validationContext.errors[0]
                                        }}</b-form-invalid-feedback
                                    >
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <!-- Reminder At (Optional) -->
                        <b-col md="6" sm="12" v-if="followup.type !== 'note'">
                            <b-form-group
                                :label="
                                    $t('Reminder_At') +
                                    ' (' +
                                    $t('Optional') +
                                    ')'
                                "
                            >
                                <b-form-input
                                    type="datetime-local"
                                    v-model="followup.reminder_at"
                                ></b-form-input>
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
                                    v-model="followup.assigned_to"
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
                                    v-model="followup.status"
                                />
                            </b-form-group>
                        </b-col>

                        <!-- Description -->
                        <b-col md="12" sm="12">
                            <b-form-group :label="$t('Description')">
                                <b-form-textarea
                                    v-model="followup.description"
                                    rows="4"
                                    :placeholder="$t('Enter_Description')"
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
                                @click="goBack"
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
import axios from "axios";

export default {
    name: "crm-create-followup",
    metaInfo: {
        title: "CRM Followup",
    },
    data() {
        return {
            isLoading: true,
            SubmitProcessing: false,
            isEditMode: false,
            followup: {
                id: null,
                type: "call",
                subject: "",
                description: "",
                deal_id: null,
                client_id: null,
                scheduled_at: null,
                reminder_at: null,
                assigned_to: null,
                status: "scheduled",
            },
            clients: [],
            deals: [],
            agents: [],
            typeOptions: [
                { label: "Call", value: "call" },
                { label: "Meeting", value: "meeting" },
                { label: "Email", value: "email" },
                { label: "Task", value: "task" },
                { label: "Note", value: "note" },
            ],
            statusOptions: [
                { label: "Scheduled", value: "scheduled" },
                { label: "Completed", value: "completed" },
                { label: "Cancelled", value: "cancelled" },
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
        //---------------------------------------- On Type Change
        onTypeChange() {
            // Notes don't need scheduling
            if (this.followup.type === "note") {
                this.followup.scheduled_at = null;
                this.followup.status = "completed";
            } else {
                this.followup.status = "scheduled";
            }
        },
        //---------------------------------------- Fetch Clients
        async Fetch_Clients() {
            try {
                const response = await axios.get("clients", {
                    params: { limit: -1 },
                });
                this.clients =
                    response.data.clients || response.data.data || [];
            } catch (error) {
                console.error("Error fetching clients:", error);
            }
        },
        //---------------------------------------- Fetch Deals
        async Fetch_Deals(clientId = null) {
            try {
                const params = { limit: -1 };
                if (clientId) params.client_id = clientId;
                const response = await axios.get("crm/deals", { params });
                this.deals = response.data.deals || response.data.data || [];
            } catch (error) {
                console.error("Error fetching deals:", error);
            }
        },
        //---------------------------------------- Fetch Agents
        async Fetch_Agents() {
            try {
                const response = await axios.get("users", {
                    params: { role: "crm_agent", limit: -1 },
                });
                this.agents = response.data.users || response.data.data || [];
            } catch (error) {
                console.error("Error fetching agents:", error);
            }
        },
        //------------- Submit Validation
        Submit_Followup() {
            this.$refs.Create_Followup.validate().then((success) => {
                if (!success) {
                    this.makeToast(
                        "danger",
                        this.$t("Please_fill_the_form_correctly"),
                        this.$t("Failed")
                    );
                } else {
                    if (this.isEditMode) {
                        this.Update_Followup();
                    } else {
                        this.Create_Followup();
                    }
                }
            });
        },
        //---------------------------------------- Create Followup
        Create_Followup() {
            this.SubmitProcessing = true;
            NProgress.start();
            const data = {
                type: this.followup.type,
                subject: this.followup.subject,
                description: this.followup.description,
                deal_id: this.followup.deal_id,
                client_id: this.followup.client_id,
                scheduled_at: this.followup.scheduled_at,
                reminder_at: this.followup.reminder_at,
                assigned_to: this.followup.assigned_to,
                status: this.followup.status || "scheduled",
            };

            axios
                .post("crm/followups", data)
                .then((response) => {
                    this.makeToast(
                        "success",
                        this.$t("Successfully_Created"),
                        this.$t("Success")
                    );
                    this.SubmitProcessing = false;
                    NProgress.done();
                    this.goBack();
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
        //---------------------------------------- Update Followup
        Update_Followup() {
            this.SubmitProcessing = true;
            NProgress.start();
            const data = {
                type: this.followup.type,
                subject: this.followup.subject,
                description: this.followup.description,
                deal_id: this.followup.deal_id,
                client_id: this.followup.client_id,
                scheduled_at: this.followup.scheduled_at,
                reminder_at: this.followup.reminder_at,
                assigned_to: this.followup.assigned_to,
                status: this.followup.status,
                completed_at: this.followup.completed_at,
            };

            axios
                .put(`crm/followups/${this.followup.id}`, data)
                .then(() => {
                    this.makeToast(
                        "success",
                        this.$t("Successfully_Updated"),
                        this.$t("Success")
                    );
                    this.SubmitProcessing = false;
                    NProgress.done();
                    this.goBack();
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
        //---------------------------------------- Get Followup Data
        async Get_Followup(id) {
            NProgress.start();
            try {
                const response = await axios.get(`crm/followups/${id}`);
                this.followup = response.data.followup || response.data.data;
                // Format datetime for input fields
                if (this.followup.scheduled_at) {
                    this.followup.scheduled_at = this.formatDateTimeLocal(
                        this.followup.scheduled_at
                    );
                }
                if (this.followup.reminder_at) {
                    this.followup.reminder_at = this.formatDateTimeLocal(
                        this.followup.reminder_at
                    );
                }
                // Load deals for selected client
                if (this.followup.client_id) {
                    await this.Fetch_Deals(this.followup.client_id);
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
                    this.goBack();
                }, 1500);
            }
        },
        //---------------------------------------- Format DateTime Local
        formatDateTimeLocal(dateString) {
            if (!dateString) return "";
            const date = new Date(dateString);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");
            const hours = String(date.getHours()).padStart(2, "0");
            const minutes = String(date.getMinutes()).padStart(2, "0");
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        },
        //---------------------------------------- Go Back
        goBack() {
            const dealId = this.$route.query.deal_id;
            const clientId = this.$route.query.client_id;
            if (dealId) {
                this.$router.push({
                    name: "crm-deal-detail",
                    params: { id: dealId },
                });
            } else if (clientId) {
                this.$router.push({
                    name: "crm-contact-detail",
                    params: { id: clientId },
                });
            } else {
                this.$router.push({ name: "crm-followups" });
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
            this.Fetch_Deals(),
            this.Fetch_Agents(),
        ]);

        const id = this.$route.params.id;
        const dealId = this.$route.query.deal_id;
        const clientId = this.$route.query.client_id;

        if (dealId) {
            this.followup.deal_id = parseInt(dealId);
            await this.Fetch_Deals();
        }
        if (clientId) {
            this.followup.client_id = parseInt(clientId);
            await this.Fetch_Deals(clientId);
        }

        if (id && id !== "new") {
            this.isEditMode = true;
            await this.Get_Followup(id);
        } else {
            this.followup.status = "scheduled";
            this.isLoading = false;
        }
    },
};
</script>
