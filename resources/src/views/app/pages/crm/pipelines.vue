<template>
    <div class="main-content">
        <breadcumb :page="$t('Pipelines')" :folder="$t('CRM')" />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <div v-else>
            <div slot="table-actions" class="mt-2 mb-3">
                <b-button
                    v-if="can('crm_pipelines_add')"
                    @click="New_Pipeline()"
                    size="sm"
                    variant="btn btn-primary btn-icon m-1"
                >
                    <i class="i-Add"></i>
                    <span class="ml-1">{{ $t("Add_Pipeline") }}</span>
                </b-button>
            </div>

            <vue-good-table
                mode="remote"
                :columns="columns"
                :totalRows="totalRows"
                :rows="pipelines"
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
                    <span v-if="props.column.field === 'color'">
                        <span
                            class="badge"
                            :style="{
                                backgroundColor: props.row.color,
                                color: '#fff',
                                padding: '8px 16px',
                            }"
                        >
                            {{ props.row.color }}
                        </span>
                    </span>
                    <span v-else-if="props.column.field === 'is_default'">
                        <span
                            v-if="props.row.is_default"
                            class="badge badge-success"
                            >{{ $t("Yes") }}</span
                        >
                        <span v-else class="badge badge-secondary">{{
                            $t("No")
                        }}</span>
                    </span>
                    <span v-else-if="props.column.field === 'stages_count'">
                        <span class="badge badge-info">{{
                            props.row.stages_count || 0
                        }}</span>
                    </span>
                    <span v-else-if="props.column.field === 'actions'">
                        <router-link
                            v-if="can('crm_pipelines_view')"
                            v-b-tooltip.hover
                            title="View/Edit Stages"
                            :to="{
                                name: 'crm_pipeline_stages',
                                params: { id: props.row.id },
                            }"
                        >
                            <i class="i-Eye text-25 text-info"></i>
                        </router-link>
                        <router-link
                            v-if="can('crm_pipelines_edit')"
                            v-b-tooltip.hover
                            title="Edit"
                            :to="{
                                name: 'crm_pipeline_edit',
                                params: { id: props.row.id },
                            }"
                        >
                            <i class="i-Edit text-25 text-success"></i>
                        </router-link>
                        <a
                            v-if="can('crm_pipelines_delete')"
                            @click="Remove_Pipeline(props.row.id)"
                            v-b-tooltip.hover
                            title="Delete"
                            class="cursor-pointer"
                        >
                            <i class="i-Close-Window text-25 text-danger"></i>
                        </a>
                    </span>
                    <span v-else>
                        {{ props.formattedRow[props.column.field] }}
                    </span>
                </template>
            </vue-good-table>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";

export default {
    name: "crm-pipelines",
    metaInfo: { title: "CRM Pipelines" },
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
            pipelines: [],
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
                    label: this.$t("Description"),
                    field: "description",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Color"),
                    field: "color",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Default"),
                    field: "is_default",
                    tdClass: "text-center",
                    thClass: "text-center",
                },
                {
                    label: this.$t("Stages"),
                    field: "stages_count",
                    tdClass: "text-center",
                    thClass: "text-center",
                },
                {
                    label: this.$t("Sort_Order"),
                    field: "sort_order",
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
        New_Pipeline() {
            this.$router.push({ name: "crm_pipeline_create" });
        },
        updateParams(newProps) {
            this.serverParams = Object.assign({}, this.serverParams, newProps);
        },
        onPageChange({ currentPage }) {
            if (this.serverParams.page !== currentPage) {
                this.updateParams({ page: currentPage });
                this.Get_Pipelines(currentPage);
            }
        },
        onPerPageChange({ currentPerPage }) {
            if (this.limit !== currentPerPage) {
                this.limit = currentPerPage;
                this.updateParams({ page: 1, perPage: currentPerPage });
                this.Get_Pipelines(1);
            }
        },
        onSortChange(params) {
            this.updateParams({
                sort: { type: params[0].type, field: params[0].field },
            });
            this.Get_Pipelines(this.serverParams.page);
        },
        onSearch(value) {
            this.search = value.searchTerm;
            this.Get_Pipelines(this.serverParams.page);
        },
        Get_Pipelines(page) {
            NProgress.start();
            NProgress.set(0.1);
            window.axios
                .get(
                    "crm/pipelines?page=" +
                        page +
                        "&SortField=" +
                        encodeURIComponent(this.serverParams.sort.field) +
                        "&SortType=" +
                        encodeURIComponent(this.serverParams.sort.type) +
                        "&search=" +
                        encodeURIComponent(this.search || "") +
                        "&limit=" +
                        encodeURIComponent(this.limit)
                )
                .then((response) => {
                    this.pipelines =
                        response.data.pipelines || response.data.data || [];
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
        Remove_Pipeline(id) {
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
                    NProgress.set(0.1);
                    window.axios
                        .delete("crm/pipelines/" + id)
                        .then(() => {
                            this.$swal(
                                this.$t("Delete_Deleted"),
                                this.$t("Deleted_in_successfully"),
                                "success"
                            );
                            this.Get_Pipelines(this.serverParams.page);
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
        this.Get_Pipelines(1);
    },
};
</script>
