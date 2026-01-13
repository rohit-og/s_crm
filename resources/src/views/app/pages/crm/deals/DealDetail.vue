<template>
    <div class="main-content">
        <breadcumb :page="$t('Deal_Details')" :folder="$t('CRM')" />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <div v-else-if="!deal" class="alert alert-warning">
            <h5>{{ $t("Error") }}</h5>
            <p>{{ $t("Deal_not_found_or_failed_to_load") }}</p>
            <b-button
                variant="primary"
                @click="$router.push({ name: 'crm-deals' })"
            >
                {{ $t("Back_to_Deals") }}
            </b-button>
        </div>
        <div v-else-if="deal">
            <!-- Deal Header -->
            <b-card class="mb-4 shadow-sm">
                <b-row class="align-items-center">
                    <b-col md="8">
                        <h4 class="mb-2">
                            <i
                                class="i-File-Clipboard-File--Text mr-2 text-primary"
                            ></i>
                            {{ deal.name }}
                        </h4>
                        <div class="text-muted">
                            <span class="mr-3"
                                ><strong>{{ $t("Status") }}:</strong>
                                <span
                                    :class="
                                        'badge badge-' +
                                        getStatusBadge(deal.status)
                                    "
                                    class="ml-1"
                                >
                                    {{ deal.status }}
                                </span></span
                            >
                            <span class="mr-3" v-if="deal.stage"
                                ><strong>{{ $t("Stage") }}:</strong>
                                <span
                                    class="badge ml-1"
                                    :style="{
                                        backgroundColor: deal.stage.color,
                                        color: '#fff',
                                    }"
                                >
                                    {{ deal.stage.name }}
                                </span></span
                            >
                            <span class="mr-3" v-if="deal.probability !== null"
                                ><strong>{{ $t("Probability") }}:</strong>
                                <span class="badge badge-info ml-1">
                                    {{ deal.probability }}%
                                </span></span
                            >
                        </div>
                    </b-col>
                    <b-col md="4" class="text-right">
                        <b-button-group>
                            <b-button
                                v-if="can('crm_deals_edit')"
                                variant="primary"
                                size="sm"
                                :to="{
                                    name: 'crm_deal_edit',
                                    params: { id: deal.id },
                                }"
                            >
                                <i class="i-Edit"></i>
                                {{ $t("Edit") }}
                            </b-button>
                            <b-button
                                variant="secondary"
                                size="sm"
                                @click="$router.push({ name: 'crm-deals' })"
                            >
                                <i class="i-Left"></i>
                                {{ $t("Back") }}
                            </b-button>
                            <b-button
                                v-if="can('crm_deals_delete')"
                                variant="danger"
                                size="sm"
                                @click="Remove_Deal"
                            >
                                <i class="i-Close-Window"></i>
                                {{ $t("Delete") }}
                            </b-button>
                        </b-button-group>
                    </b-col>
                </b-row>
            </b-card>

            <b-row>
                <!-- Deal Information -->
                <b-col md="8">
                    <b-card class="mb-4">
                        <h5 class="mb-3">
                            <i class="i-Information mr-2"></i
                            >{{ $t("Deal_Information") }}
                        </h5>
                        <table class="table table-hover table-bordered">
                            <tbody>
                                <tr>
                                    <td width="40%">{{ $t("Name") }}</td>
                                    <th>{{ deal.name }}</th>
                                </tr>
                                <tr v-if="deal.description">
                                    <td>{{ $t("Description") }}</td>
                                    <th>{{ deal.description }}</th>
                                </tr>
                                <tr v-if="deal.client">
                                    <td>{{ $t("Client") }}</td>
                                    <th>
                                        <router-link
                                            :to="{
                                                name: 'crm-contact-detail',
                                                params: { id: deal.client.id },
                                            }"
                                            >{{ deal.client.name }}</router-link
                                        >
                                    </th>
                                </tr>
                                <tr v-if="deal.pipeline">
                                    <td>{{ $t("Pipeline") }}</td>
                                    <th>{{ deal.pipeline.name }}</th>
                                </tr>
                                <tr v-if="deal.stage">
                                    <td>{{ $t("Stage") }}</td>
                                    <th>
                                        <span
                                            class="badge"
                                            :style="{
                                                backgroundColor:
                                                    deal.stage.color,
                                                color: '#fff',
                                                padding: '6px 12px',
                                            }"
                                        >
                                            {{ deal.stage.name }}
                                        </span>
                                    </th>
                                </tr>
                                <tr>
                                    <td>{{ $t("Value") }}</td>
                                    <th>
                                        {{
                                            formatPriceWithSymbol(
                                                deal.currency ||
                                                    (currentUser &&
                                                        currentUser.currency) ||
                                                    "",
                                                deal.value,
                                                2
                                            )
                                        }}
                                    </th>
                                </tr>
                                <tr v-if="deal.probability !== null">
                                    <td>{{ $t("Probability") }}</td>
                                    <th>
                                        <b-progress
                                            :value="deal.probability"
                                            :max="100"
                                            show-progress
                                            class="mb-2"
                                        ></b-progress>
                                        {{ deal.probability }}%
                                    </th>
                                </tr>
                                <tr v-if="deal.expected_close_date">
                                    <td>{{ $t("Expected_Close_Date") }}</td>
                                    <th>
                                        {{
                                            formatDate(deal.expected_close_date)
                                        }}
                                    </th>
                                </tr>
                                <tr v-if="deal.actual_close_date">
                                    <td>{{ $t("Actual_Close_Date") }}</td>
                                    <th>
                                        {{ formatDate(deal.actual_close_date) }}
                                    </th>
                                </tr>
                                <tr>
                                    <td>{{ $t("Status") }}</td>
                                    <th>
                                        <span
                                            :class="
                                                'badge badge-' +
                                                getStatusBadge(deal.status)
                                            "
                                        >
                                            {{ deal.status }}
                                        </span>
                                    </th>
                                </tr>
                                <tr v-if="deal.assigned_user">
                                    <td>{{ $t("Assigned_To") }}</td>
                                    <th>{{ deal.assigned_user.name }}</th>
                                </tr>
                                <tr>
                                    <td>{{ $t("Created_At") }}</td>
                                    <th>
                                        {{ formatDateTime(deal.created_at) }}
                                    </th>
                                </tr>
                                <tr v-if="deal.updated_at">
                                    <td>{{ $t("Updated_At") }}</td>
                                    <th>
                                        {{ formatDateTime(deal.updated_at) }}
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    </b-card>

                    <!-- Related Followups -->
                    <b-card>
                        <div
                            class="d-flex justify-content-between align-items-center mb-3"
                        >
                            <h5 class="mb-0">
                                <i class="i-Calendar-3 mr-2"></i
                                >{{ $t("Related_Followups") }}
                            </h5>
                            <b-button
                                v-if="can('crm_followups_add')"
                                variant="primary"
                                size="sm"
                                @click="New_Followup"
                            >
                                <i class="i-Add"></i>
                                {{ $t("Add_Followup") }}
                            </b-button>
                        </div>
                        <div
                            v-if="followups.length === 0"
                            class="text-center text-muted py-4"
                        >
                            {{ $t("No_followups_found") }}
                        </div>
                        <div v-else>
                            <div
                                v-for="followup in followups"
                                :key="followup.id"
                                class="border-bottom pb-3 mb-3"
                            >
                                <div
                                    class="d-flex justify-content-between align-items-start"
                                >
                                    <div>
                                        <h6 class="mb-1">
                                            <router-link
                                                :to="{
                                                    name: 'crm-followup-detail',
                                                    params: { id: followup.id },
                                                }"
                                                >{{
                                                    followup.subject
                                                }}</router-link
                                            >
                                        </h6>
                                        <small class="text-muted">
                                            <i class="i-Calendar-3"></i>
                                            {{
                                                formatDateTime(
                                                    followup.scheduled_at
                                                )
                                            }}
                                        </small>
                                        <p
                                            v-if="followup.description"
                                            class="mb-0 mt-2 text-muted"
                                        >
                                            {{ followup.description }}
                                        </p>
                                    </div>
                                    <span
                                        :class="
                                            'badge badge-' +
                                            getFollowupTypeColor(followup.type)
                                        "
                                    >
                                        {{ followup.type }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </b-card>
                </b-col>

                <!-- Quick Actions Sidebar -->
                <b-col md="4">
                    <b-card class="mb-4">
                        <h5 class="mb-3">
                            <i class="i-Settings mr-2"></i
                            >{{ $t("Quick_Actions") }}
                        </h5>
                        <b-button
                            v-if="
                                deal.pipeline_id &&
                                deal.stage &&
                                stages.length > 0
                            "
                            variant="outline-primary"
                            size="sm"
                            block
                            class="mb-2"
                            @click="Show_Move_Stage_Modal"
                        >
                            <i class="i-Arrow-Right"></i>
                            {{ $t("Move_To_Stage") }}
                        </b-button>
                        <b-button
                            v-if="can('crm_deals_assign')"
                            variant="outline-info"
                            size="sm"
                            block
                            class="mb-2"
                            @click="Show_Assign_Modal"
                        >
                            <i class="i-Administrator"></i>
                            {{ $t("Assign_Agent") }}
                        </b-button>
                        <b-button
                            v-if="can('crm_followups_add')"
                            variant="outline-success"
                            size="sm"
                            block
                            class="mb-2"
                            @click="New_Followup"
                        >
                            <i class="i-Add"></i>
                            {{ $t("Create_Followup") }}
                        </b-button>
                    </b-card>

                    <b-card>
                        <h5 class="mb-3">
                            <i class="i-Bar-Chart mr-2"></i
                            >{{ $t("Statistics") }}
                        </h5>
                        <div class="mb-3">
                            <small class="text-muted d-block">{{
                                $t("Deal_Value")
                            }}</small>
                            <h4 class="text-primary mb-0">
                                {{
                                    formatPriceWithSymbol(
                                        deal.currency ||
                                            (currentUser &&
                                                currentUser.currency) ||
                                            "",
                                        deal.value,
                                        2
                                    )
                                }}
                            </h4>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">{{
                                $t("Expected_Value")
                            }}</small>
                            <h4 class="text-success mb-0">
                                {{
                                    formatPriceWithSymbol(
                                        deal.currency ||
                                            (currentUser &&
                                                currentUser.currency) ||
                                            "",
                                        (deal.value * (deal.probability || 0)) /
                                            100,
                                        2
                                    )
                                }}
                            </h4>
                            <small class="text-muted"
                                >{{ deal.probability || 0 }}% probability</small
                            >
                        </div>
                        <div>
                            <small class="text-muted d-block">{{
                                $t("Total_Followups")
                            }}</small>
                            <h4 class="text-info mb-0">
                                {{ followups.length }}
                            </h4>
                        </div>
                    </b-card>
                </b-col>
            </b-row>

            <!-- Move Stage Modal -->
            <b-modal
                id="moveStageModal"
                :title="$t('Move_Deal_To_Stage')"
                @ok="Move_To_Stage"
                @hidden="moveStageForm.pipeline_stage_id = null"
            >
                <b-form-group :label="$t('Select_Stage')">
                    <v-select
                        :options="stages"
                        label="name"
                        :reduce="(option) => option.id"
                        :placeholder="$t('Select_Stage')"
                        v-model="moveStageForm.pipeline_stage_id"
                    />
                </b-form-group>
            </b-modal>

            <!-- Assign Agent Modal -->
            <b-modal
                id="assignModal"
                :title="$t('Assign_Agent')"
                @ok="Assign_Agent"
                @hidden="assignForm.agent_id = null"
            >
                <b-form-group :label="$t('Select_Agent')">
                    <v-select
                        :options="agents"
                        label="name"
                        :reduce="(option) => option.id"
                        :placeholder="$t('Select_Agent')"
                        v-model="assignForm.agent_id"
                    />
                </b-form-group>
            </b-modal>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";
// Use window.axios which has baseURL configured in main.js

export default {
    name: "crm-deal-detail",
    metaInfo: {
        title: "Deal Details",
    },
    data() {
        return {
            isLoading: true,
            deal: null,
            followups: [],
            stages: [],
            agents: [],
            moveStageForm: {
                pipeline_stage_id: null,
            },
            assignForm: {
                agent_id: null,
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
        //---------------------------------------- Get Deal Data
        async Get_Deal(id) {
            NProgress.start();
            try {
                const [dealRes, followupsRes] = await Promise.all([
                    window.axios.get(`crm/deals/${id}`),
                    window.axios.get(`crm/deals/${id}/followups`).catch(() => ({
                        data: { followups: [] },
                    })),
                ]);

                // DealController show() returns the deal directly, not wrapped
                this.deal =
                    dealRes.data.deal || dealRes.data.data || dealRes.data;

                // Ensure deal is not null before proceeding
                if (!this.deal) {
                    throw new Error("Deal not found");
                }

                this.followups =
                    followupsRes.data.followups || followupsRes.data.data || [];

                // Fetch stages if pipeline exists
                if (this.deal && this.deal.pipeline_id) {
                    await this.Fetch_Pipeline_Stages(this.deal.pipeline_id);
                }

                // Fetch agents
                await this.Fetch_Agents();

                this.isLoading = false;
                NProgress.done();
            } catch (error) {
                console.error("Error loading deal:", error);
                this.makeToast(
                    "danger",
                    error.response?.data?.message ||
                        error.message ||
                        this.$t("Failed_to_load_deal"),
                    this.$t("Error")
                );
                this.deal = null; // Ensure deal is null so error message shows
                this.isLoading = false;
                NProgress.done();
                // Don't auto-redirect, let user see the error and choose to go back
            }
        },
        //---------------------------------------- Fetch Pipeline Stages
        async Fetch_Pipeline_Stages(pipelineId) {
            try {
                const response = await window.axios.get(
                    `crm/pipelines/${pipelineId}/stages`
                );
                this.stages = response.data.stages || response.data.data || [];
            } catch (error) {
                console.error("Error fetching stages:", error);
                this.stages = [];
            }
        },
        //---------------------------------------- Fetch Agents
        async Fetch_Agents() {
            try {
                const response = await window.axios.get("users", {
                    params: { limit: -1 },
                });
                this.agents = response.data.users || response.data.data || [];
            } catch (error) {
                console.error("Error fetching agents:", error);
                this.agents = [];
            }
        },
        //---------------------------------------- Remove Deal
        Remove_Deal() {
            if (!this.deal || !this.deal.id) {
                this.makeToast(
                    "danger",
                    this.$t("Deal_not_loaded"),
                    this.$t("Error")
                );
                return;
            }
            this.$swal({
                title: this.$t("Delete_Title"),
                text: this.$t("Delete_Text"),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                cancelButtonText: this.$t("Delete_cancelButtonText"),
                confirmButtonText: this.$t("Delete_confirmButtonText"),
            }).then((result) => {
                if (result.value) {
                    NProgress.start();
                    window.axios
                        .delete(`crm/deals/${this.deal.id}`)
                        .then(() => {
                            this.$swal(
                                this.$t("Delete_Deleted"),
                                this.$t("Deleted_in_successfully"),
                                "success"
                            );
                            this.$router.push({ name: "crm-deals" });
                        })
                        .catch(() => {
                            NProgress.done();
                            this.$swal(
                                this.$t("Delete_Failed"),
                                this.$t("Delete.Therewassomethingwronge"),
                                "warning"
                            );
                        });
                }
            });
        },
        //---------------------------------------- Show Move Stage Modal
        Show_Move_Stage_Modal() {
            if (!this.deal) return;
            this.moveStageForm.pipeline_stage_id = this.deal.pipeline_stage_id;
            this.$bvModal.show("moveStageModal");
        },
        //---------------------------------------- Move To Stage
        Move_To_Stage(bvModalEvt) {
            bvModalEvt.preventDefault();
            if (!this.deal || !this.deal.id) {
                this.makeToast(
                    "danger",
                    this.$t("Deal_not_loaded"),
                    this.$t("Error")
                );
                return;
            }
            if (!this.moveStageForm.pipeline_stage_id) {
                this.makeToast(
                    "warning",
                    this.$t("Please_select_a_stage"),
                    this.$t("Validation_Error")
                );
                return;
            }

            NProgress.start();
            window.axios
                .post(`crm/deals/${this.deal.id}/move-stage`, {
                    pipeline_stage_id: this.moveStageForm.pipeline_stage_id,
                })
                .then(() => {
                    this.$swal(
                        this.$t("Success"),
                        this.$t("Deal_moved_successfully"),
                        "success"
                    );
                    this.$bvModal.hide("moveStageModal");
                    this.Get_Deal(this.deal.id);
                })
                .catch((error) => {
                    this.$swal(
                        this.$t("Error"),
                        error.response?.data?.message ||
                            this.$t("Failed_to_move_deal"),
                        "error"
                    );
                })
                .finally(() => {
                    NProgress.done();
                });
        },
        //---------------------------------------- Show Assign Modal
        Show_Assign_Modal() {
            if (!this.deal) return;
            this.assignForm.agent_id = this.deal.assigned_to;
            this.$bvModal.show("assignModal");
        },
        //---------------------------------------- Assign Agent
        Assign_Agent(bvModalEvt) {
            bvModalEvt.preventDefault();
            if (!this.deal || !this.deal.id) {
                this.makeToast(
                    "danger",
                    this.$t("Deal_not_loaded"),
                    this.$t("Error")
                );
                return;
            }
            NProgress.start();
            window.axios
                .post(`crm/deals/${this.deal.id}/assign`, {
                    assigned_to: this.assignForm.agent_id,
                })
                .then(() => {
                    this.$swal(
                        this.$t("Success"),
                        this.$t("Agent_assigned_successfully"),
                        "success"
                    );
                    this.$bvModal.hide("assignModal");
                    this.Get_Deal(this.deal.id);
                })
                .catch((error) => {
                    this.$swal(
                        this.$t("Error"),
                        error.response?.data?.message ||
                            this.$t("Failed_to_assign_agent"),
                        "error"
                    );
                })
                .finally(() => {
                    NProgress.done();
                });
        },
        //---------------------------------------- New Followup
        New_Followup() {
            if (!this.deal || !this.deal.id) {
                this.makeToast(
                    "danger",
                    this.$t("Deal_not_loaded"),
                    this.$t("Error")
                );
                return;
            }
            this.$router.push({
                name: "crm-followup-create",
                query: { deal_id: this.deal.id },
            });
        },
        //---------------------------------------- Format Methods
        formatPriceWithSymbol(symbol, number, dec) {
            const safeSymbol = symbol || "";
            const value = Number(number || 0).toFixed(dec || 2);
            return safeSymbol ? `${safeSymbol} ${value}` : value;
        },
        formatDate(date) {
            if (!date) return "";
            return new Date(date).toLocaleDateString();
        },
        formatDateTime(date) {
            if (!date) return "";
            return new Date(date).toLocaleString();
        },
        getStatusBadge(status) {
            const badges = {
                open: "primary",
                closed_won: "success",
                closed_lost: "danger",
            };
            return badges[status] || "secondary";
        },
        getFollowupTypeColor(type) {
            const colors = {
                call: "primary",
                meeting: "success",
                email: "info",
                task: "warning",
                note: "secondary",
            };
            return colors[type] || "secondary";
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
        if (id) {
            this.Get_Deal(id);
        } else {
            this.$router.push({ name: "crm-deals" });
        }
    },
};
</script>
