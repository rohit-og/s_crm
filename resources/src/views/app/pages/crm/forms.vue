<template>
    <div class="main-content">
        <breadcumb :page="$t('Form Builder')" :folder="$t('CRM')" />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <div v-else>
            <div slot="table-actions" class="mt-2 mb-3">
                <b-button
                    v-if="can('crm_forms_add')"
                    @click="New_Form()"
                    size="sm"
                    variant="btn btn-primary btn-icon m-1"
                >
                    <i class="i-Add"></i>
                    <span class="ml-1">{{ $t("Create_Form") }}</span>
                </b-button>
            </div>

            <vue-good-table
                mode="remote"
                :columns="columns"
                :totalRows="totalRows"
                :rows="forms"
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
                    <span v-if="props.column.field === 'is_active'">
                        <span
                            v-if="props.row.is_active"
                            class="badge badge-success"
                            >{{ $t("Active") }}</span
                        >
                        <span v-else class="badge badge-secondary">{{
                            $t("Inactive")
                        }}</span>
                    </span>
                    <span
                        v-else-if="props.column.field === 'submissions_count'"
                    >
                        <router-link
                            :to="{
                                name: 'crm_form_submissions',
                                params: { formId: props.row.id },
                            }"
                            class="badge badge-info"
                        >
                            {{ props.row.submissions_count || 0 }}
                        </router-link>
                    </span>
                    <span v-else-if="props.column.field === 'actions'">
                        <a
                            v-if="can('crm_forms_publish')"
                            @click="Toggle_Publish(props.row)"
                            v-b-tooltip.hover
                            :title="
                                props.row.is_active
                                    ? $t('Unpublish')
                                    : $t('Publish')
                            "
                            class="cursor-pointer mr-2"
                        >
                            <i
                                :class="
                                    props.row.is_active
                                        ? 'i-Close text-25 text-warning'
                                        : 'i-Check text-25 text-success'
                                "
                            ></i>
                        </a>
                        <router-link
                            v-if="can('crm_forms_view')"
                            v-b-tooltip.hover
                            title="View/Edit"
                            :to="{
                                name: 'crm_form_builder',
                                params: { id: props.row.id },
                            }"
                        >
                            <i class="i-Eye text-25 text-info"></i>
                        </router-link>
                        <a
                            v-if="can('crm_forms_edit')"
                            @click="Duplicate_Form(props.row.id)"
                            v-b-tooltip.hover
                            title="Duplicate"
                            class="cursor-pointer ml-2"
                        >
                            <i class="i-File-Copy text-25 text-warning"></i>
                        </a>
                        <a
                            v-if="can('crm_forms_delete')"
                            @click="Remove_Form(props.row.id)"
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
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";
import axios from "axios";

export default {
    name: "crm-forms",
    metaInfo: { title: "CRM Forms" },
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
            forms: [],
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
                    label: this.$t("Status"),
                    field: "is_active",
                    tdClass: "text-center",
                    thClass: "text-center",
                },
                {
                    label: this.$t("Submissions"),
                    field: "submissions_count",
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
        New_Form() {
            this.$router.push({
                name: "crm_form_builder",
                params: { id: "new" },
            });
        },
        updateParams(newProps) {
            this.serverParams = Object.assign({}, this.serverParams, newProps);
        },
        onPageChange({ currentPage }) {
            if (this.serverParams.page !== currentPage) {
                this.updateParams({ page: currentPage });
                this.Get_Forms(currentPage);
            }
        },
        onPerPageChange({ currentPerPage }) {
            if (this.limit !== currentPerPage) {
                this.limit = currentPerPage;
                this.updateParams({ page: 1, perPage: currentPerPage });
                this.Get_Forms(1);
            }
        },
        onSortChange(params) {
            this.updateParams({
                sort: { type: params[0].type, field: params[0].field },
            });
            this.Get_Forms(this.serverParams.page);
        },
        onSearch(value) {
            this.search = value.searchTerm;
            this.Get_Forms(this.serverParams.page);
        },
        Get_Forms(page) {
            NProgress.start();
            NProgress.set(0.1);
            axios
                .get(
                    "crm/forms?page=" +
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
                    this.forms =
                        response.data.forms || response.data.data || [];
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
        Toggle_Publish(form) {
            NProgress.start();
            axios
                .post(`crm/forms/${form.id}/publish`, {
                    is_active: !form.is_active,
                })
                .then(() => {
                    this.$swal(
                        this.$t("Success"),
                        this.$t("Form_updated_successfully"),
                        "success"
                    );
                    this.Get_Forms(this.serverParams.page);
                })
                .catch(() => {
                    this.$swal(
                        this.$t("Error"),
                        this.$t("Failed_to_update_form"),
                        "error"
                    );
                })
                .finally(() => {
                    NProgress.done();
                });
        },
        Duplicate_Form(id) {
            this.$swal({
                title: this.$t("Confirm"),
                text: this.$t("Are_you_sure_you_want_to_duplicate_this_form"),
                type: "question",
                showCancelButton: true,
                confirmButtonText: this.$t("Yes"),
                cancelButtonText: this.$t("Cancel"),
            }).then((result) => {
                if (result.value) {
                    NProgress.start();
                    axios
                        .post(`crm/forms/${id}/duplicate`)
                        .then(() => {
                            this.$swal(
                                this.$t("Success"),
                                this.$t("Form_duplicated_successfully"),
                                "success"
                            );
                            this.Get_Forms(this.serverParams.page);
                        })
                        .catch(() => {
                            this.$swal(
                                this.$t("Error"),
                                this.$t("Failed_to_duplicate_form"),
                                "error"
                            );
                        })
                        .finally(() => {
                            NProgress.done();
                        });
                }
            });
        },
        Remove_Form(id) {
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
                        .delete("crm/forms/" + id)
                        .then(() => {
                            this.$swal(
                                this.$t("Delete_Deleted"),
                                this.$t("Deleted_in_successfully"),
                                "success"
                            );
                            this.Get_Forms(this.serverParams.page);
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
        this.Get_Forms(1);
    },
};
</script>
