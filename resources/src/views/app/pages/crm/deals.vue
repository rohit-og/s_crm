<template>
    <div class="main-content">
        <breadcumb :page="$t('Deals')" :folder="$t('CRM')" />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <div v-else>
            <!-- View Toggle -->
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <b-button-group class="mr-3">
                        <b-button
                            :variant="
                                viewMode === 'list'
                                    ? 'primary'
                                    : 'outline-primary'
                            "
                            @click="viewMode = 'list'"
                        >
                            <i class="i-List"></i> {{ $t("List") }}
                        </b-button>
                        <b-button
                            :variant="
                                viewMode === 'kanban'
                                    ? 'primary'
                                    : 'outline-primary'
                            "
                            @click="viewMode = 'kanban'"
                        >
                            <i class="i-Columns"></i> {{ $t("Kanban") }}
                        </b-button>
                    </b-button-group>
                    <!-- Pipeline Selector for Kanban View -->
                    <div
                        v-if="viewMode === 'kanban'"
                        class="mr-3"
                        style="min-width: 250px"
                    >
                        <v-select
                            v-model="selectedPipeline"
                            :options="pipelines"
                            label="name"
                            :reduce="(option) => option.id"
                            :placeholder="$t('Select_Pipeline')"
                            @input="onPipelineChange"
                        />
                    </div>
                </div>
                <div>
                    <b-button
                        variant="outline-info m-1"
                        size="sm"
                        v-b-toggle.sidebar-right
                    >
                        <i class="i-Filter-2"></i> {{ $t("Filter") }}
                    </b-button>
                    <b-button
                        v-if="can('crm_deals_add')"
                        @click="New_Deal()"
                        size="sm"
                        variant="btn btn-primary btn-icon m-1"
                    >
                        <i class="i-Add"></i>
                        <span class="ml-1">{{ $t("Add_Deal") }}</span>
                    </b-button>
                </div>
            </div>

            <!-- List View -->
            <div v-if="viewMode === 'list'">
                <vue-good-table
                    mode="remote"
                    :columns="columns"
                    :totalRows="totalRows"
                    :rows="deals"
                    @on-page-change="onPageChange"
                    @on-per-page-change="onPerPageChange"
                    @on-sort-change="onSortChange"
                    @on-search="onSearch"
                    :search-options="{
                        enabled: true,
                        placeholder: $t('Search_this_table'),
                    }"
                    :pagination-options="{
                        enabled: true,
                        mode: 'records',
                        nextLabel: 'next',
                        prevLabel: 'prev',
                    }"
                    styleClass="tableOne vgt-table"
                >
                    <template slot="table-row" slot-scope="props">
                        <span v-if="props.column.field === 'value'">
                            {{
                                formatPriceWithSymbol(
                                    currentUser.currency,
                                    props.row.value,
                                    2
                                )
                            }}
                        </span>
                        <span v-else-if="props.column.field === 'status'">
                            <span
                                :class="
                                    'badge badge-' +
                                    getStatusBadge(props.row.status)
                                "
                            >
                                {{ props.row.status }}
                            </span>
                        </span>
                        <span v-else-if="props.column.field === 'probability'">
                            <b-progress
                                :value="props.row.probability"
                                :max="100"
                                show-progress
                                class="mb-2"
                            ></b-progress>
                        </span>
                        <span v-else-if="props.column.field === 'actions'">
                            <router-link
                                v-if="can('crm_deals_view')"
                                v-b-tooltip.hover
                                title="View"
                                :to="{
                                    name: 'crm_deal_detail',
                                    params: { id: props.row.id },
                                }"
                            >
                                <i class="i-Eye text-25 text-info"></i>
                            </router-link>
                            <router-link
                                v-if="can('crm_deals_edit')"
                                v-b-tooltip.hover
                                title="Edit"
                                :to="{
                                    name: 'crm_deal_edit',
                                    params: { id: props.row.id },
                                }"
                            >
                                <i class="i-Edit text-25 text-success"></i>
                            </router-link>
                            <a
                                v-if="can('crm_deals_delete')"
                                @click="Remove_Deal(props.row.id)"
                                v-b-tooltip.hover
                                title="Delete"
                                class="cursor-pointer"
                            >
                                <i
                                    class="i-Close-Window text-25 text-danger"
                                ></i>
                            </a>
                        </span>
                        <span v-else>
                            {{ props.formattedRow[props.column.field] }}
                        </span>
                    </template>
                </vue-good-table>
            </div>

            <!-- Kanban View -->
            <div
                v-else-if="viewMode === 'kanban' && selectedPipeline"
                class="kanban-container"
            >
                <div class="row">
                    <div
                        v-for="stage in stages"
                        :key="stage.id"
                        class="col-md-3 mb-4"
                        @drop="onDrop($event, stage.id)"
                        @dragover.prevent
                        @dragenter.prevent
                    >
                        <div class="card">
                            <div
                                class="card-header"
                                :style="{
                                    backgroundColor: stage.color,
                                    color: '#fff',
                                }"
                            >
                                <h6 class="mb-0">
                                    {{ stage.name }} ({{
                                        getDealsByStage(stage.id).length
                                    }})
                                </h6>
                            </div>
                            <div
                                class="card-body p-2"
                                style="
                                    min-height: 400px;
                                    max-height: 600px;
                                    overflow-y: auto;
                                "
                            >
                                <div
                                    v-for="deal in getDealsByStage(stage.id)"
                                    :key="deal.id"
                                    class="card mb-2 deal-card"
                                    draggable="true"
                                    @dragstart="onDragStart($event, deal.id)"
                                >
                                    <div class="card-body p-2">
                                        <h6 class="card-title mb-1">
                                            {{ deal.name }}
                                        </h6>
                                        <small
                                            class="text-muted d-block mb-1"
                                            >{{ deal.client_name }}</small
                                        >
                                        <div
                                            class="d-flex justify-content-between align-items-center"
                                        >
                                            <span
                                                class="font-weight-bold text-success"
                                            >
                                                {{
                                                    formatPriceWithSymbol(
                                                        currentUser.currency,
                                                        deal.value,
                                                        2
                                                    )
                                                }}
                                            </span>
                                            <span class="badge badge-primary"
                                                >{{ deal.probability }}%</span
                                            >
                                        </div>
                                        <div class="mt-2">
                                            <router-link
                                                :to="{
                                                    name: 'crm_deal_detail',
                                                    params: { id: deal.id },
                                                }"
                                                class="btn btn-sm btn-outline-primary btn-block"
                                            >
                                                {{ $t("View") }}
                                            </router-link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-else-if="viewMode === 'kanban' && !selectedPipeline"
                class="alert alert-info"
            >
                {{ $t("Please_select_a_pipeline_to_view_kanban") }}
            </div>

            <!-- Filter Sidebar -->
            <b-sidebar
                id="sidebar-right"
                :title="$t('Filter')"
                bg-variant="white"
                right
                shadow
            >
                <div class="px-3 py-2">
                    <b-form-group :label="$t('Pipeline')">
                        <v-select
                            v-model="filterPipeline"
                            :options="pipelines"
                            label="name"
                            :reduce="(option) => option.id"
                            :placeholder="$t('Select_Pipeline')"
                            @input="Get_Deals(serverParams.page)"
                        />
                    </b-form-group>
                    <b-form-group :label="$t('Status')">
                        <v-select
                            v-model="filterStatus"
                            :options="statusOptions"
                            :placeholder="$t('Select_Status')"
                            @input="Get_Deals(serverParams.page)"
                        />
                    </b-form-group>
                    <b-button
                        @click="Reset_Filter()"
                        variant="danger"
                        size="sm"
                        block
                    >
                        {{ $t("Reset") }}
                    </b-button>
                </div>
            </b-sidebar>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";
// Use window.axios which has baseURL configured in main.js

export default {
    name: "crm-deals",
    metaInfo: { title: "CRM Deals" },
    data() {
        return {
            serverParams: {
                sort: { field: "id", type: "desc" },
                page: 1,
                perPage: 10,
            },
            search: "",
            totalRows: "",
            isLoading: true,
            limit: "10",
            deals: [],
            viewMode: "list",
            pipelines: [],
            stages: [],
            selectedPipeline: null,
            filterPipeline: null,
            filterStatus: null,
            statusOptions: [
                { label: "Open", value: "open" },
                { label: "Closed Won", value: "closed_won" },
                { label: "Closed Lost", value: "closed_lost" },
            ],
            draggedDealId: null,
        };
    },
    computed: {
        ...mapGetters(["currentUserPermissions", "currentUser"]),
        columns() {
            return [
                {
                    label: this.$t("ID"),
                    field: "id",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Name"),
                    field: "name",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Client"),
                    field: "client_name",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Pipeline"),
                    field: "pipeline_name",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Stage"),
                    field: "stage_name",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Value"),
                    field: "value",
                    tdClass: "text-right",
                    thClass: "text-right",
                },
                {
                    label: this.$t("Probability"),
                    field: "probability",
                    tdClass: "text-center",
                    thClass: "text-center",
                },
                {
                    label: this.$t("Status"),
                    field: "status",
                    tdClass: "text-center",
                    thClass: "text-center",
                },
                {
                    label: this.$t("Action"),
                    field: "actions",
                    tdClass: "text-right",
                    thClass: "text-right",
                    sortable: false,
                },
            ];
        },
    },
    methods: {
        can(p) {
            return (
                this.currentUserPermissions &&
                this.currentUserPermissions.includes(p)
            );
        },
        formatPriceWithSymbol(symbol, number, dec) {
            const safeSymbol = symbol || "";
            const value = Number(number || 0).toFixed(dec || 2);
            return safeSymbol ? `${safeSymbol} ${value}` : value;
        },
        getStatusBadge(status) {
            const badges = {
                open: "primary",
                closed_won: "success",
                closed_lost: "danger",
            };
            return badges[status] || "secondary";
        },
        New_Deal() {
            this.$router.push({ name: "crm_deal_create" });
        },
        getDealsByStage(stageId) {
            return this.deals.filter((d) => d.pipeline_stage_id === stageId);
        },
        onDragStart(event, dealId) {
            this.draggedDealId = dealId;
        },
        onDrop(event, stageId) {
            if (this.draggedDealId) {
                this.Move_Deal_To_Stage(this.draggedDealId, stageId);
                this.draggedDealId = null;
            }
        },
        Move_Deal_To_Stage(dealId, stageId) {
            NProgress.start();
            axios
                .post(`crm/deals/${dealId}/move-stage`, {
                    pipeline_stage_id: stageId,
                })
                .then(() => {
                    this.Get_Deals(this.serverParams.page);
                    this.$swal(
                        this.$t("Success"),
                        this.$t("Deal_moved_successfully"),
                        "success"
                    );
                })
                .catch(() => {
                    this.$swal(
                        this.$t("Error"),
                        this.$t("Failed_to_move_deal"),
                        "error"
                    );
                })
                .finally(() => {
                    NProgress.done();
                });
        },
        updateParams(newProps) {
            this.serverParams = Object.assign({}, this.serverParams, newProps);
        },
        onPageChange({ currentPage }) {
            if (this.serverParams.page !== currentPage) {
                this.updateParams({ page: currentPage });
                this.Get_Deals(currentPage);
            }
        },
        onPerPageChange({ currentPerPage }) {
            if (this.limit !== currentPerPage) {
                this.limit = currentPerPage;
                this.updateParams({ page: 1, perPage: currentPerPage });
                this.Get_Deals(1);
            }
        },
        onSortChange(params) {
            this.updateParams({
                sort: { type: params[0].type, field: params[0].field },
            });
            this.Get_Deals(this.serverParams.page);
        },
        onSearch(value) {
            this.search = value.searchTerm;
            this.Get_Deals(this.serverParams.page);
        },
        Reset_Filter() {
            this.filterPipeline = null;
            this.filterStatus = null;
            this.Get_Deals(this.serverParams.page);
        },
        async Get_Deals(page) {
            NProgress.start();
            NProgress.set(0.1);
            const params = {
                page,
                SortField: this.serverParams.sort.field,
                SortType: this.serverParams.sort.type,
                search: this.search || "",
                limit: this.limit,
            };
            if (this.filterPipeline) params.pipeline_id = this.filterPipeline;
            if (this.filterStatus) params.status = this.filterStatus;

            try {
                const response = await window.axios.get("crm/deals", {
                    params,
                });
                this.deals = response.data.deals || response.data.data || [];
                this.totalRows =
                    response.data.totalRows || response.data.total || 0;

                // If kanban view and pipeline selected, fetch stages
                if (this.viewMode === "kanban" && this.filterPipeline) {
                    await this.Fetch_Pipeline_Stages(this.filterPipeline);
                }
            } catch (error) {
                console.error("Error fetching deals:", error);
            } finally {
                NProgress.done();
                this.isLoading = false;
            }
        },
        async Fetch_Pipelines() {
            try {
                const response = await window.axios.get("crm/pipelines", {
                    params: { limit: -1 },
                });
                this.pipelines =
                    response.data.pipelines || response.data.data || [];
                if (this.pipelines.length > 0 && !this.filterPipeline) {
                    this.filterPipeline = this.pipelines[0].id;
                    this.selectedPipeline = this.filterPipeline;
                }
            } catch (error) {
                console.error("Error fetching pipelines:", error);
            }
        },
        async Fetch_Pipeline_Stages(pipelineId) {
            try {
                const response = await window.axios.get(
                    `crm/pipelines/${pipelineId}/stages`
                );
                this.stages = response.data.stages || response.data.data || [];
                if (this.viewMode === "kanban") {
                    await this.Get_Deals(this.serverParams.page);
                }
            } catch (error) {
                console.error("Error fetching stages:", error);
            }
        },
        async onPipelineChange(pipelineId) {
            if (pipelineId) {
                this.filterPipeline = pipelineId;
                await this.Fetch_Pipeline_Stages(pipelineId);
            } else {
                this.selectedPipeline = null;
                this.stages = [];
            }
        },
        Remove_Deal(id) {
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
                        .delete("crm/deals/" + id)
                        .then(() => {
                            this.$swal(
                                this.$t("Delete_Deleted"),
                                this.$t("Deleted_in_successfully"),
                                "success"
                            );
                            this.Get_Deals(this.serverParams.page);
                        })
                        .catch(() => {
                            setTimeout(() => NProgress.done(), 500);
                            this.$swal(
                                this.$t("Delete_Failed"),
                                this.$t("Delete.Therewassomethingwronge"),
                                "warning"
                            );
                        });
                }
            });
        },
    },
    async created() {
        await this.Fetch_Pipelines();
        this.Get_Deals(1);
    },
};
</script>

<style scoped>
.kanban-container {
    overflow-x: auto;
}
.deal-card {
    cursor: move;
}
.deal-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
</style>
