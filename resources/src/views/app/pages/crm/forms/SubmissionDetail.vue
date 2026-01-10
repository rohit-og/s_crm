<template>
    <div class="main-content">
        <breadcumb :page="$t('Submission_Details')" :folder="$t('CRM')" />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <div v-else-if="submission">
            <!-- Submission Header -->
            <b-card class="mb-4 shadow-sm">
                <b-row class="align-items-center">
                    <b-col md="8">
                        <h4 class="mb-2">
                            <i
                                class="i-File-Horizontal-Text mr-2 text-primary"
                            ></i>
                            {{ $t("Submission_Details") }} #{{ submission.id }}
                        </h4>
                        <div class="text-muted">
                            <span class="mr-3"
                                ><strong>{{ $t("Form") }}:</strong>
                                {{ form?.name || $t("Unknown") }}</span
                            >
                            <span class="mr-3" v-if="submission.submitted_at"
                                ><strong>{{ $t("Submitted_At") }}:</strong>
                                {{
                                    formatDateTime(submission.submitted_at)
                                }}</span
                            >
                            <span class="mr-3" v-if="submission.client"
                                ><strong>{{ $t("Contact") }}:</strong>
                                <router-link
                                    :to="{
                                        name: 'crm-contact-detail',
                                        params: { id: submission.client.id },
                                    }"
                                    >{{ submission.client.name }}</router-link
                                ></span
                            >
                            <span class="mr-3" v-else
                                ><span class="badge badge-warning">{{
                                    $t("Not_Matched")
                                }}</span></span
                            >
                        </div>
                    </b-col>
                    <b-col md="4" class="text-right">
                        <b-button-group>
                            <b-button
                                variant="secondary"
                                size="sm"
                                @click="goBack"
                            >
                                <i class="i-Left"></i>
                                {{ $t("Back") }}
                            </b-button>
                            <b-button
                                v-if="
                                    can('crm_form_submissions_match') &&
                                    !submission.client_id
                                "
                                variant="success"
                                size="sm"
                                @click="Match_To_Contact"
                            >
                                <i class="i-User"></i>
                                {{ $t("Match_to_Contact") }}
                            </b-button>
                            <b-button
                                variant="outline-primary"
                                size="sm"
                                @click="Export_Submission"
                            >
                                <i class="i-File-Excel"></i>
                                {{ $t("Export") }}
                            </b-button>
                            <b-button
                                v-if="can('crm_form_submissions_delete')"
                                variant="danger"
                                size="sm"
                                @click="Remove_Submission"
                            >
                                <i class="i-Close-Window"></i>
                                {{ $t("Delete") }}
                            </b-button>
                        </b-button-group>
                    </b-col>
                </b-row>
            </b-card>

            <b-row>
                <!-- Submission Data -->
                <b-col md="8">
                    <b-card class="mb-4">
                        <h5 class="mb-3">
                            <i class="i-Information mr-2"></i
                            >{{ $t("Submission_Data") }}
                        </h5>
                        <div
                            v-if="submissionData.length === 0"
                            class="text-center text-muted py-4"
                        >
                            {{ $t("No_data_found") }}
                        </div>
                        <table v-else class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th width="40%">{{ $t("Field") }}</th>
                                    <th>{{ $t("Value") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in submissionData"
                                    :key="index"
                                >
                                    <td>
                                        <strong>{{ item.label }}</strong>
                                    </td>
                                    <td>
                                        <span v-if="item.type === 'file'">
                                            <a
                                                v-for="(
                                                    file, fileIndex
                                                ) in item.value"
                                                :key="fileIndex"
                                                :href="file.url"
                                                target="_blank"
                                                class="mr-2"
                                            >
                                                <i class="i-File-Download"></i>
                                                {{ file.name }}
                                            </a>
                                        </span>
                                        <span
                                            v-else-if="
                                                Array.isArray(item.value)
                                            "
                                        >
                                            {{ item.value.join(", ") }}
                                        </span>
                                        <span v-else>{{
                                            item.value || $t("N/A")
                                        }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </b-card>

                    <!-- Additional Information -->
                    <b-card>
                        <h5 class="mb-3">
                            <i class="i-Information mr-2"></i
                            >{{ $t("Additional_Information") }}
                        </h5>
                        <table class="table table-hover table-bordered">
                            <tbody>
                                <tr v-if="submission.ip_address">
                                    <td width="40%">{{ $t("IP_Address") }}</td>
                                    <th>{{ submission.ip_address }}</th>
                                </tr>
                                <tr v-if="submission.user_agent">
                                    <td>{{ $t("User_Agent") }}</td>
                                    <th>{{ submission.user_agent }}</th>
                                </tr>
                                <tr>
                                    <td>{{ $t("Submitted_At") }}</td>
                                    <th>
                                        {{
                                            formatDateTime(
                                                submission.submitted_at
                                            )
                                        }}
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    </b-card>
                </b-col>

                <!-- Actions Sidebar -->
                <b-col md="4">
                    <b-card class="mb-4">
                        <h5 class="mb-3">
                            <i class="i-Settings mr-2"></i
                            >{{ $t("Quick_Actions") }}
                        </h5>
                        <b-button
                            v-if="form"
                            variant="outline-primary"
                            size="sm"
                            block
                            class="mb-2"
                            :to="{
                                name: 'crm-form-builder',
                                params: { id: form.id },
                            }"
                        >
                            <i class="i-File-Horizontal-Text"></i>
                            {{ $t("View_Form") }}
                        </b-button>
                        <b-button
                            v-if="submission.client"
                            variant="outline-info"
                            size="sm"
                            block
                            class="mb-2"
                            :to="{
                                name: 'crm-contact-detail',
                                params: { id: submission.client.id },
                            }"
                        >
                            <i class="i-User"></i>
                            {{ $t("View_Contact") }}
                        </b-button>
                        <b-button
                            v-if="
                                can('crm_form_submissions_match') &&
                                !submission.client_id
                            "
                            variant="success"
                            size="sm"
                            block
                            class="mb-2"
                            @click="Match_To_Contact"
                        >
                            <i class="i-User-Plus"></i>
                            {{ $t("Match_to_Contact") }}
                        </b-button>
                    </b-card>
                </b-col>
            </b-row>

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
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";
import axios from "axios";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

export default {
    name: "crm-submission-detail",
    metaInfo: {
        title: "Submission Details",
    },
    data() {
        return {
            isLoading: true,
            submission: null,
            form: null,
            submissionData: [],
            contacts: [],
            matchForm: {
                contact_id: null,
                email: null,
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
        //---------------------------------------- Get Submission Data
        async Get_Submission(id) {
            NProgress.start();
            try {
                const response = await axios.get(`crm/form-submissions/${id}`);
                this.submission =
                    response.data.submission || response.data.data;

                // Get form data
                if (this.submission.form_id) {
                    await this.Get_Form(this.submission.form_id);
                }

                // Parse submission data
                this.Parse_Submission_Data();

                // Fetch contacts for matching
                await this.Fetch_Contacts();

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
        //---------------------------------------- Get Form
        async Get_Form(formId) {
            try {
                const response = await axios.get(`crm/forms/${formId}`);
                this.form = response.data.form || response.data.data;
                this.Parse_Submission_Data();
            } catch (error) {
                console.error("Error fetching form:", error);
            }
        },
        //---------------------------------------- Parse Submission Data
        Parse_Submission_Data() {
            if (!this.submission || !this.form) return;

            const formFields =
                this.form.form_fields &&
                typeof this.form.form_fields === "string"
                    ? JSON.parse(this.form.form_fields)
                    : this.form.form_fields || [];

            const submissionDataObj =
                this.submission.data && typeof this.submission.data === "string"
                    ? JSON.parse(this.submission.data)
                    : this.submission.data || {};

            this.submissionData = formFields.map((field) => ({
                label: field.label,
                type: field.type,
                value: submissionDataObj[field.id] || "",
            }));
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
        Match_To_Contact() {
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
                    `crm/form-submissions/${this.submission.id}/match-contact`,
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
                    this.Get_Submission(this.submission.id);
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
        Remove_Submission() {
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
                        .delete(`crm/form-submissions/${this.submission.id}`)
                        .then(() => {
                            this.$swal(
                                this.$t("Delete_Deleted"),
                                this.$t("Deleted_in_successfully"),
                                "success"
                            );
                            this.goBack();
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
        //---------------------------------------- Export Submission
        Export_Submission() {
            const pdf = new jsPDF("p", "pt");

            pdf.setFontSize(16);
            pdf.text(this.$t("Submission_Details"), 40, 40);

            pdf.setFontSize(12);
            let yPos = 70;

            if (this.form) {
                pdf.text(`${this.$t("Form")}: ${this.form.name}`, 40, yPos);
                yPos += 20;
            }

            if (this.submission.submitted_at) {
                pdf.text(
                    `${this.$t("Submitted_At")}: ${this.formatDateTime(
                        this.submission.submitted_at
                    )}`,
                    40,
                    yPos
                );
                yPos += 20;
            }

            yPos += 10;

            const headers = [this.$t("Field"), this.$t("Value")];
            const body = this.submissionData.map((item) => [
                item.label,
                Array.isArray(item.value)
                    ? item.value.join(", ")
                    : item.value || this.$t("N/A"),
            ]);

            autoTable(pdf, {
                head: [headers],
                body,
                startY: yPos,
                theme: "striped",
                styles: { fontSize: 9, cellPadding: 4 },
            });

            pdf.save(`Submission_${this.submission.id}.pdf`);
        },
        //---------------------------------------- Format DateTime
        formatDateTime(date) {
            if (!date) return "";
            return new Date(date).toLocaleString();
        },
        //---------------------------------------- Go Back
        goBack() {
            const formId = this.$route.query.formId || this.submission?.form_id;
            if (formId) {
                this.$router.push({
                    name: "crm-form-submissions",
                    params: { formId },
                });
            } else {
                this.$router.push({ name: "crm-forms" });
            }
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
            this.Get_Submission(id);
        } else {
            this.$router.push({ name: "crm-forms" });
        }
    },
};
</script>
