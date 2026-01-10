<template>
    <div class="main-content">
        <breadcumb :page="$t('Form_Submissions')" :folder="$t('CRM')" />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <div v-else>
            <b-card class="mb-4">
                <b-row class="align-items-center mb-3">
                    <b-col md="8">
                        <h4 class="mb-0">
                            <i
                                class="i-File-Horizontal-Text mr-2 text-primary"
                            ></i>
                            {{ form?.name || $t("Form_Submissions") }}
                        </h4>
                        <small class="text-muted" v-if="form">{{
                            form.description
                        }}</small>
                    </b-col>
                    <b-col md="4" class="text-right">
                        <b-button
                            variant="secondary"
                            size="sm"
                            class="mr-2"
                            @click="goBack"
                        >
                            <i class="i-Left"></i>
                            {{ $t("Back") }}
                        </b-button>
                        <b-button
                            v-if="submissions.length > 0"
                            variant="outline-success"
                            size="sm"
                            @click="Export_Submissions"
                        >
                            <i class="i-File-Excel"></i>
                            {{ $t("Export") }}
                        </b-button>
                    </b-col>
                </b-row>
            </b-card>

            <b-card>
                <vue-good-table
                    mode="remote"
                    :columns="columns"
                    :totalRows="totalRows"
                    :rows="submissions"
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
                        <span v-if="props.column.field === 'submitted_at'">
                            {{ formatDateTime(props.row.submitted_at) }}
                        </span>
                        <span v-else-if="props.column.field === 'client'">
                            <router-link
                                v-if="props.row.client"
                                :to="{
                                    name: 'crm-contact-detail',
                                    params: { id: props.row.client.id },
                                }"
                                >{{ props.row.client.name }}</router-link
                            >
                            <span v-else class="text-muted">{{
                                $t("Not_Matched")
                            }}</span>
                        </span>
                        <span v-else-if="props.column.field === 'actions'">
                            <router-link
                                v-if="can('crm_form_submissions_view')"
                                v-b-tooltip.hover
                                title="View"
                                :to="{
                                    name: 'crm-submission-detail',
                                    params: { id: props.row.id },
                                }"
                            >
                                <i class="i-Eye text-25 text-info"></i>
                            </router-link>
                            <a
                                v-if="
                                    can('crm_form_submissions_match') &&
                                    !props.row.client_id
                                "
                                @click="Match_To_Contact(props.row)"
                                v-b-tooltip.hover
                                title="Match to Contact"
                                class="cursor-pointer ml-2"
                            >
                                <i class="i-User text-25 text-success"></i>
                            </a>
                            <a
                                v-if="can('crm_form_submissions_delete')"
                                @click="Remove_Submission(props.row.id)"
                                v-b-tooltip.hover
                                title="Delete"
                                class="cursor-pointer ml-2"
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
            </b-card>
        </div>

        <!-- Match to Contact Modal -->
        <b-modal
            id="matchContactModal"
            :title="$t('Match_Submission_To_Contact')"
            @ok="Confirm_Match_Contact"
            @hidden="matchForm.contact_id = null"
        >
            <b-form-group :label="$t('Select_Contact')">
                <v-select
                    :options="contacts"
                    label="name"
                    :reduce="(option) => option.id"
                    :placeholder="$t('Select_Contact')"
                    v-model="matchForm.contact_id"
                />
                <small class="text-muted">{{
                    $t("Or_search_by_email_to_create_new_contact")
                }}</small>
            </b-form-group>
            <b-form-group :label="$t('Email')">
                <b-form-input
                    v-model="matchForm.email"
                    type="email"
                    :placeholder="$t('Enter_Email_To_Create_New_Contact')"
                ></b-form-input>
            </b-form-group>
        </b-modal>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";
import axios from "axios";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

export default {
    name: "crm-form-submissions",
    metaInfo: {
        title: "Form Submissions",
    },
    data() {
        return {
            isLoading: true,
            form: null,
            formId: null,
            serverParams: {
                sort: { field: "submitted_at", type: "desc" },
                page: 1,
                perPage: 10,
            },
            search: "",
            totalRows: "",
            limit: "10",
            submissions: [],
            contacts: [],
            matchForm: {
                submission_id: null,
                contact_id: null,
                email: null,
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
                    label: this.$t("Submitted_At"),
                    field: "submitted_at",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Contact"),
                    field: "client",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("IP_Address"),
                    field: "ip_address",
                    tdClass: "text-left",
                    thClass: "text-left",
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
        //---------------------------------------- Get Form Data
        async Get_Form() {
            try {
                const response = await axios.get(`crm/forms/${this.formId}`);
                this.form = response.data.form || response.data.data;
            } catch (error) {
                console.error("Error fetching form:", error);
            }
        },
        //---------------------------------------- Get Submissions
        Get_Submissions(page) {
            NProgress.start();
            NProgress.set(0.1);
            axios
                .get(
                    `crm/forms/${this.formId}/submissions?page=` +
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
                    this.submissions =
                        response.data.submissions || response.data.data || [];
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
        //---------------------------------------- Fetch Contacts
        async Fetch_Contacts() {
            try {
                const response = await axios.get("crm/contacts", {
                    params: { limit: -1 },
                });
                this.contacts =
                    response.data.contacts || response.data.data || [];
            } catch (error) {
                console.error("Error fetching contacts:", error);
            }
        },
        //---------------------------------------- Match To Contact
        Match_To_Contact(submission) {
            this.matchForm.submission_id = submission.id;
            this.matchForm.contact_id = null;
            this.matchForm.email = null;
            this.$bvModal.show("matchContactModal");
        },
        //---------------------------------------- Confirm Match Contact
        Confirm_Match_Contact(bvModalEvt) {
            bvModalEvt.preventDefault();
            if (!this.matchForm.contact_id && !this.matchForm.email) {
                this.makeToast(
                    "warning",
                    this.$t("Please_select_or_enter_contact_information"),
                    this.$t("Validation_Error")
                );
                return;
            }

            NProgress.start();
            axios
                .post(
                    `crm/form-submissions/${this.matchForm.submission_id}/match-contact`,
                    {
                        contact_id: this.matchForm.contact_id,
                        email: this.matchForm.email,
                    }
                )
                .then(() => {
                    this.$swal(
                        this.$t("Success"),
                        this.$t("Submission_matched_successfully"),
                        "success"
                    );
                    this.$bvModal.hide("matchContactModal");
                    this.Get_Submissions(this.serverParams.page);
                })
                .catch((error) => {
                    this.$swal(
                        this.$t("Error"),
                        error.response?.data?.message ||
                            this.$t("Failed_to_match_submission"),
                        "error"
                    );
                })
                .finally(() => {
                    NProgress.done();
                });
        },
        //---------------------------------------- Remove Submission
        Remove_Submission(id) {
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
                        .delete(`crm/form-submissions/${id}`)
                        .then(() => {
                            this.$swal(
                                this.$t("Delete_Deleted"),
                                this.$t("Deleted_in_successfully"),
                                "success"
                            );
                            this.Get_Submissions(this.serverParams.page);
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
        //---------------------------------------- Export Submissions
        Export_Submissions() {
            const pdf = new jsPDF("p", "pt");
            const headers = [
                this.$t("ID"),
                this.$t("Submitted_At"),
                this.$t("Contact"),
                this.$t("IP_Address"),
            ];
            const body = this.submissions.map((s) => [
                s.id,
                this.formatDateTime(s.submitted_at),
                s.client?.name || this.$t("Not_Matched"),
                s.ip_address || "",
            ]);

            autoTable(pdf, {
                head: [headers],
                body,
                startY: 110,
                theme: "striped",
                styles: { fontSize: 9, cellPadding: 4 },
            });

            pdf.save(`Form_Submissions_${this.formId}.pdf`);
        },
        //---------------------------------------- Format DateTime
        formatDateTime(date) {
            if (!date) return "";
            return new Date(date).toLocaleString();
        },
        //---------------------------------------- Go Back
        goBack() {
            this.$router.push({ name: "crm-forms" });
        },
        updateParams(newProps) {
            this.serverParams = Object.assign({}, this.serverParams, newProps);
        },
        onPageChange({ currentPage }) {
            if (this.serverParams.page !== currentPage) {
                this.updateParams({ page: currentPage });
                this.Get_Submissions(currentPage);
            }
        },
        onPerPageChange({ currentPerPage }) {
            if (this.limit !== currentPerPage) {
                this.limit = currentPerPage;
                this.updateParams({ page: 1, perPage: currentPerPage });
                this.Get_Submissions(1);
            }
        },
        onSortChange(params) {
            this.updateParams({
                sort: { type: params[0].type, field: params[0].field },
            });
            this.Get_Submissions(this.serverParams.page);
        },
        onSearch(value) {
            this.search = value.searchTerm;
            this.Get_Submissions(this.serverParams.page);
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
        this.formId = this.$route.params.formId;
        if (this.formId) {
            await Promise.all([this.Get_Form(), this.Fetch_Contacts()]);
            this.Get_Submissions(1);
        } else {
            this.$router.push({ name: "crm-forms" });
        }
    },
};
</script>
