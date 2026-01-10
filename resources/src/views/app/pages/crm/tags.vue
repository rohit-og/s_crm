<template>
    <div class="main-content">
        <breadcumb :page="$t('Tags')" :folder="$t('CRM')" />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <div v-else>
            <div slot="table-actions" class="mt-2 mb-3">
                <b-button
                    v-if="can('crm_tags_add')"
                    @click="New_Tag()"
                    size="sm"
                    variant="btn btn-primary btn-icon m-1"
                >
                    <i class="i-Add"></i>
                    <span class="ml-1">{{ $t("Add_Tag") }}</span>
                </b-button>
            </div>

            <vue-good-table
                mode="remote"
                :columns="columns"
                :totalRows="totalRows"
                :rows="tags"
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
                    <span v-else-if="props.column.field === 'contacts_count'">
                        <span class="badge badge-info">{{
                            props.row.contacts_count || 0
                        }}</span>
                    </span>
                    <span v-else-if="props.column.field === 'actions'">
                        <a
                            v-if="can('crm_tags_edit')"
                            @click="Edit_Tag(props.row)"
                            v-b-tooltip.hover
                            title="Edit"
                            class="cursor-pointer mr-2"
                        >
                            <i class="i-Edit text-25 text-success"></i>
                        </a>
                        <a
                            v-if="can('crm_tags_delete')"
                            @click="Remove_Tag(props.row.id)"
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

        <!-- Add/Edit Tag Modal -->
        <b-modal
            :id="'tagModal'"
            :title="isEditing ? $t('Edit_Tag') : $t('Add_Tag')"
            @ok="Save_Tag"
            @hidden="Reset_Modal"
        >
            <b-form>
                <b-form-group :label="$t('Name')">
                    <b-form-input v-model="form.name" required></b-form-input>
                </b-form-group>
                <b-form-group :label="$t('Color')">
                    <b-form-input
                        v-model="form.color"
                        type="color"
                    ></b-form-input>
                </b-form-group>
            </b-form>
        </b-modal>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";
import axios from "axios";

export default {
    name: "crm-tags",
    metaInfo: { title: "CRM Tags" },
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
            tags: [],
            isEditing: false,
            form: {
                id: null,
                name: "",
                color: "#007bff",
            },
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
                    label: this.$t("Color"),
                    field: "color",
                    tdClass: "text-center",
                    thClass: "text-center",
                },
                {
                    label: this.$t("Contacts"),
                    field: "contacts_count",
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
        New_Tag() {
            this.isEditing = false;
            this.Reset_Modal();
            this.$bvModal.show("tagModal");
        },
        Edit_Tag(tag) {
            this.isEditing = true;
            this.form = { ...tag };
            this.$bvModal.show("tagModal");
        },
        Reset_Modal() {
            this.form = {
                id: null,
                name: "",
                color: "#007bff",
            };
        },
        Save_Tag() {
            if (!this.form.name) {
                this.$swal(
                    this.$t("Validation_Error"),
                    this.$t("Name_is_required"),
                    "error"
                );
                return;
            }

            NProgress.start();
            const url = this.isEditing
                ? `crm/tags/${this.form.id}`
                : "crm/tags";
            const method = this.isEditing ? "put" : "post";

            axios[method](url, this.form)
                .then(() => {
                    this.$swal(
                        this.$t("Success"),
                        this.$t("Tag_saved_successfully"),
                        "success"
                    );
                    this.$bvModal.hide("tagModal");
                    this.Get_Tags(this.serverParams.page);
                })
                .catch(() => {
                    this.$swal(
                        this.$t("Error"),
                        this.$t("Failed_to_save_tag"),
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
                this.Get_Tags(currentPage);
            }
        },
        onPerPageChange({ currentPerPage }) {
            if (this.limit !== currentPerPage) {
                this.limit = currentPerPage;
                this.updateParams({ page: 1, perPage: currentPerPage });
                this.Get_Tags(1);
            }
        },
        onSortChange(params) {
            this.updateParams({
                sort: { type: params[0].type, field: params[0].field },
            });
            this.Get_Tags(this.serverParams.page);
        },
        onSearch(value) {
            this.search = value.searchTerm;
            this.Get_Tags(this.serverParams.page);
        },
        Get_Tags(page) {
            NProgress.start();
            NProgress.set(0.1);
            axios
                .get(
                    "crm/tags?page=" +
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
                    this.tags = response.data.tags || response.data.data || [];
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
        Remove_Tag(id) {
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
                        .delete("crm/tags/" + id)
                        .then(() => {
                            this.$swal(
                                this.$t("Delete_Deleted"),
                                this.$t("Deleted_in_successfully"),
                                "success"
                            );
                            this.Get_Tags(this.serverParams.page);
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
        this.Get_Tags(1);
    },
};
</script>
