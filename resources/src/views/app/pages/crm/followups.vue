<template>
    <div class="main-content">
        <breadcumb :page="$t('Followups')" :folder="$t('CRM')" />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <div v-else>
            <div slot="table-actions" class="mt-2 mb-3">
                <b-button
                    variant="outline-info m-1"
                    size="sm"
                    v-b-toggle.sidebar-right
                >
                    <i class="i-Filter-2"></i> {{ $t("Filter") }}
                </b-button>
                <b-button
                    v-if="can('crm_followups_add')"
                    @click="New_Followup()"
                    size="sm"
                    variant="btn btn-primary btn-icon m-1"
                >
                    <i class="i-Add"></i>
                    <span class="ml-1">{{ $t("Add_Followup") }}</span>
                </b-button>
            </div>

            <vue-good-table
                mode="remote"
                :columns="columns"
                :totalRows="totalRows"
                :rows="followups"
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
                    <span v-if="props.column.field === 'type'">
                        <span
                            :class="
                                'badge badge-' + getTypeColor(props.row.type)
                            "
                        >
                            {{ props.row.type }}
                        </span>
                    </span>
                    <span v-else-if="props.column.field === 'status'">
                        <span
                            :class="
                                'badge badge-' +
                                getStatusColor(props.row.status)
                            "
                        >
                            {{ props.row.status }}
                        </span>
                    </span>
                    <span v-else-if="props.column.field === 'scheduled_at'">
                        {{ formatDateTime(props.row.scheduled_at) }}
                    </span>
                    <span v-else-if="props.column.field === 'actions'">
                        <a
                            v-if="
                                can('crm_followups_complete') &&
                                props.row.status === 'scheduled'
                            "
                            @click="Complete_Followup(props.row.id)"
                            v-b-tooltip.hover
                            title="Mark Complete"
                            class="cursor-pointer mr-2"
                        >
                            <i class="i-Check text-25 text-success"></i>
                        </a>
                        <router-link
                            v-if="can('crm_followups_view')"
                            v-b-tooltip.hover
                            title="View"
                            :to="{
                                name: 'crm_followup_detail',
                                params: { id: props.row.id },
                            }"
                        >
                            <i class="i-Eye text-25 text-info"></i>
                        </router-link>
                        <router-link
                            v-if="can('crm_followups_edit')"
                            v-b-tooltip.hover
                            title="Edit"
                            :to="{
                                name: 'crm_followup_edit',
                                params: { id: props.row.id },
                            }"
                            class="ml-2"
                        >
                            <i class="i-Edit text-25 text-success"></i>
                        </router-link>
                        <a
                            v-if="can('crm_followups_delete')"
                            @click="Remove_Followup(props.row.id)"
                            v-b-tooltip.hover
                            title="Delete"
                            class="cursor-pointer ml-2"
                        >
                            <i class="i-Close-Window text-25 text-danger"></i>
                        </a>
                    </span>
                    <span v-else>
                        {{ props.formattedRow[props.column.field] }}
                    </span>
                </template>
            </vue-good-table>

            <!-- Filter Sidebar -->
            <b-sidebar
                id="sidebar-right"
                :title="$t('Filter')"
                bg-variant="white"
                right
                shadow
            >
                <div class="px-3 py-2">
                    <b-form-group :label="$t('Status')">
                        <v-select
                            v-model="filterStatus"
                            :options="statusOptions"
                            :placeholder="$t('Select_Status')"
                            @input="Get_Followups(serverParams.page)"
                        />
                    </b-form-group>
                    <b-form-group :label="$t('Type')">
                        <v-select
                            v-model="filterType"
                            :options="typeOptions"
                            :placeholder="$t('Select_Type')"
                            @input="Get_Followups(serverParams.page)"
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
import axios from "axios";

export default {
    name: "crm-followups",
    metaInfo: { title: "CRM Followups" },
    data() {
        return {
            serverParams: {
                sort: { field: "scheduled_at", type: "desc" },
                page: 1,
                perPage: 10,
            },
            search: "",
            totalRows: "",
            isLoading: true,
            limit: "10",
            followups: [],
            filterStatus: null,
            filterType: null,
            statusOptions: [
                { label: "Scheduled", value: "scheduled" },
                { label: "Completed", value: "completed" },
                { label: "Cancelled", value: "cancelled" },
            ],
            typeOptions: [
                { label: "Call", value: "call" },
                { label: "Meeting", value: "meeting" },
                { label: "Email", value: "email" },
                { label: "Task", value: "task" },
                { label: "Note", value: "note" },
            ],
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
                    label: this.$t("Type"),
                    field: "type",
                    tdClass: "text-center",
                    thClass: "text-center",
                },
                {
                    label: this.$t("Subject"),
                    field: "subject",
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
                    label: this.$t("Deal"),
                    field: "deal_name",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Scheduled_At"),
                    field: "scheduled_at",
                    tdClass: "text-left",
                    thClass: "text-left",
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
        getTypeColor(type) {
            const colors = {
                call: "primary",
                meeting: "success",
                email: "info",
                task: "warning",
                note: "secondary",
            };
            return colors[type] || "secondary";
        },
        getStatusColor(status) {
            const colors = {
                scheduled: "warning",
                completed: "success",
                cancelled: "danger",
            };
            return colors[status] || "secondary";
        },
        formatDateTime(date) {
            if (!date) return "";
            return new Date(date).toLocaleString();
        },
        New_Followup() {
            this.$router.push({ name: "crm_followup_create" });
        },
        updateParams(newProps) {
            this.serverParams = Object.assign({}, this.serverParams, newProps);
        },
        onPageChange({ currentPage }) {
            if (this.serverParams.page !== currentPage) {
                this.updateParams({ page: currentPage });
                this.Get_Followups(currentPage);
            }
        },
        onPerPageChange({ currentPerPage }) {
            if (this.limit !== currentPerPage) {
                this.limit = currentPerPage;
                this.updateParams({ page: 1, perPage: currentPerPage });
                this.Get_Followups(1);
            }
        },
        onSortChange(params) {
            this.updateParams({
                sort: { type: params[0].type, field: params[0].field },
            });
            this.Get_Followups(this.serverParams.page);
        },
        onSearch(value) {
            this.search = value.searchTerm;
            this.Get_Followups(this.serverParams.page);
        },
        Reset_Filter() {
            this.filterStatus = null;
            this.filterType = null;
            this.Get_Followups(this.serverParams.page);
        },
        Get_Followups(page) {
            NProgress.start();
            NProgress.set(0.1);
            const params = {
                page,
                SortField: this.serverParams.sort.field,
                SortType: this.serverParams.sort.type,
                search: this.search || "",
                limit: this.limit,
            };
            if (this.filterStatus) params.status = this.filterStatus;
            if (this.filterType) params.type = this.filterType;

            axios
                .get("crm/followups", { params })
                .then((response) => {
                    this.followups =
                        response.data.followups || response.data.data || [];
                    this.totalRows =
                        response.data.totalRows || response.data.total || 0;
                    NProgress.done();
                    this.isLoading = false;
                })
                .catch(() => {
                    NProgress.done();
                    setTimeout(() => {
                        this.isLoading = false;
                    }, 500);
                });
        },
        Complete_Followup(id) {
            NProgress.start();
            axios
                .post(`crm/followups/${id}/complete`)
                .then(() => {
                    this.$swal(
                        this.$t("Success"),
                        this.$t("Followup_completed_successfully"),
                        "success"
                    );
                    this.Get_Followups(this.serverParams.page);
                })
                .catch(() => {
                    this.$swal(
                        this.$t("Error"),
                        this.$t("Failed_to_complete_followup"),
                        "error"
                    );
                })
                .finally(() => {
                    NProgress.done();
                });
        },
        Remove_Followup(id) {
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
                    axios
                        .delete("crm/followups/" + id)
                        .then(() => {
                            this.$swal(
                                this.$t("Delete_Deleted"),
                                this.$t("Deleted_in_successfully"),
                                "success"
                            );
                            this.Get_Followups(this.serverParams.page);
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
    created() {
        this.Get_Followups(1);
    },
};
</script>
