<template>
    <div class="main-content">
        <breadcumb :page="$t('Followup_Details')" :folder="$t('CRM')" />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <div v-else-if="followup">
            <!-- Followup Header -->
            <b-card class="mb-4 shadow-sm">
                <b-row class="align-items-center">
                    <b-col md="8">
                        <h4 class="mb-2">
                            <i class="i-Calendar-3 mr-2 text-primary"></i>
                            {{ followup.subject }}
                        </h4>
                        <div class="text-muted">
                            <span class="mr-3"
                                ><strong>{{ $t("Type") }}:</strong>
                                <span
                                    :class="
                                        'badge badge-' +
                                        getTypeColor(followup.type)
                                    "
                                    class="ml-1"
                                >
                                    {{ followup.type }}
                                </span></span
                            >
                            <span class="mr-3"
                                ><strong>{{ $t("Status") }}:</strong>
                                <span
                                    :class="
                                        'badge badge-' +
                                        getStatusColor(followup.status)
                                    "
                                    class="ml-1"
                                >
                                    {{ followup.status }}
                                </span></span
                            >
                            <span class="mr-3" v-if="followup.scheduled_at"
                                ><strong>{{ $t("Scheduled_At") }}:</strong>
                                {{
                                    formatDateTime(followup.scheduled_at)
                                }}</span
                            >
                        </div>
                    </b-col>
                    <b-col md="4" class="text-right">
                        <b-button-group>
                            <b-button
                                v-if="
                                    can('crm_followups_complete') &&
                                    followup.status === 'scheduled'
                                "
                                variant="success"
                                size="sm"
                                @click="Complete_Followup"
                            >
                                <i class="i-Check"></i>
                                {{ $t("Mark_Complete") }}
                            </b-button>
                            <b-button
                                v-if="can('crm_followups_edit')"
                                variant="primary"
                                size="sm"
                                :to="{
                                    name: 'crm-followup-edit',
                                    params: { id: followup.id },
                                }"
                            >
                                <i class="i-Edit"></i>
                                {{ $t("Edit") }}
                            </b-button>
                            <b-button
                                variant="secondary"
                                size="sm"
                                @click="goBack"
                            >
                                <i class="i-Left"></i>
                                {{ $t("Back") }}
                            </b-button>
                            <b-button
                                v-if="can('crm_followups_delete')"
                                variant="danger"
                                size="sm"
                                @click="Remove_Followup"
                            >
                                <i class="i-Close-Window"></i>
                                {{ $t("Delete") }}
                            </b-button>
                        </b-button-group>
                    </b-col>
                </b-row>
            </b-card>

            <b-row>
                <!-- Followup Information -->
                <b-col md="8">
                    <b-card class="mb-4">
                        <h5 class="mb-3">
                            <i class="i-Information mr-2"></i
                            >{{ $t("Followup_Information") }}
                        </h5>
                        <table class="table table-hover table-bordered">
                            <tbody>
                                <tr>
                                    <td width="40%">{{ $t("Subject") }}</td>
                                    <th>{{ followup.subject }}</th>
                                </tr>
                                <tr>
                                    <td>{{ $t("Type") }}</td>
                                    <th>
                                        <span
                                            :class="
                                                'badge badge-' +
                                                getTypeColor(followup.type)
                                            "
                                        >
                                            {{ followup.type }}
                                        </span>
                                    </th>
                                </tr>
                                <tr>
                                    <td>{{ $t("Status") }}</td>
                                    <th>
                                        <span
                                            :class="
                                                'badge badge-' +
                                                getStatusColor(followup.status)
                                            "
                                        >
                                            {{ followup.status }}
                                        </span>
                                    </th>
                                </tr>
                                <tr v-if="followup.description">
                                    <td>{{ $t("Description") }}</td>
                                    <th>{{ followup.description }}</th>
                                </tr>
                                <tr v-if="followup.client">
                                    <td>{{ $t("Client") }}</td>
                                    <th>
                                        <router-link
                                            :to="{
                                                name: 'crm-contact-detail',
                                                params: {
                                                    id: followup.client.id,
                                                },
                                            }"
                                            >{{
                                                followup.client.name
                                            }}</router-link
                                        >
                                    </th>
                                </tr>
                                <tr v-if="followup.deal">
                                    <td>{{ $t("Deal") }}</td>
                                    <th>
                                        <router-link
                                            :to="{
                                                name: 'crm-deal-detail',
                                                params: {
                                                    id: followup.deal.id,
                                                },
                                            }"
                                            >{{
                                                followup.deal.name
                                            }}</router-link
                                        >
                                    </th>
                                </tr>
                                <tr v-if="followup.scheduled_at">
                                    <td>{{ $t("Scheduled_At") }}</td>
                                    <th>
                                        {{
                                            formatDateTime(
                                                followup.scheduled_at
                                            )
                                        }}
                                    </th>
                                </tr>
                                <tr v-if="followup.completed_at">
                                    <td>{{ $t("Completed_At") }}</td>
                                    <th>
                                        {{
                                            formatDateTime(
                                                followup.completed_at
                                            )
                                        }}
                                    </th>
                                </tr>
                                <tr v-if="followup.reminder_at">
                                    <td>{{ $t("Reminder_At") }}</td>
                                    <th>
                                        {{
                                            formatDateTime(followup.reminder_at)
                                        }}
                                    </th>
                                </tr>
                                <tr v-if="followup.assigned_user">
                                    <td>{{ $t("Assigned_To") }}</td>
                                    <th>{{ followup.assigned_user.name }}</th>
                                </tr>
                                <tr>
                                    <td>{{ $t("Created_At") }}</td>
                                    <th>
                                        {{
                                            formatDateTime(followup.created_at)
                                        }}
                                    </th>
                                </tr>
                            </tbody>
                        </table>
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
                                can('crm_followups_complete') &&
                                followup.status === 'scheduled'
                            "
                            variant="success"
                            size="sm"
                            block
                            class="mb-2"
                            @click="Complete_Followup"
                        >
                            <i class="i-Check"></i>
                            {{ $t("Mark_Complete") }}
                        </b-button>
                        <b-button
                            v-if="followup.deal"
                            variant="outline-primary"
                            size="sm"
                            block
                            class="mb-2"
                            :to="{
                                name: 'crm-deal-detail',
                                params: { id: followup.deal.id },
                            }"
                        >
                            <i class="i-File-Clipboard-File--Text"></i>
                            {{ $t("View_Deal") }}
                        </b-button>
                        <b-button
                            v-if="followup.client"
                            variant="outline-info"
                            size="sm"
                            block
                            class="mb-2"
                            :to="{
                                name: 'crm-contact-detail',
                                params: { id: followup.client.id },
                            }"
                        >
                            <i class="i-User"></i>
                            {{ $t("View_Client") }}
                        </b-button>
                    </b-card>
                </b-col>
            </b-row>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";
import axios from "axios";

export default {
    name: "crm-followup-detail",
    metaInfo: {
        title: "Followup Details",
    },
    data() {
        return {
            isLoading: true,
            followup: null,
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
        //---------------------------------------- Get Followup Data
        async Get_Followup(id) {
            NProgress.start();
            try {
                const response = await axios.get(`crm/followups/${id}`);
                this.followup = response.data.followup || response.data.data;
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
        //---------------------------------------- Complete Followup
        Complete_Followup() {
            this.$swal({
                title: this.$t("Confirm"),
                text: this.$t(
                    "Are_you_sure_you_want_to_mark_this_followup_as_complete"
                ),
                type: "question",
                showCancelButton: true,
                confirmButtonText: this.$t("Yes"),
                cancelButtonText: this.$t("Cancel"),
            }).then((result) => {
                if (result.value) {
                    NProgress.start();
                    axios
                        .post(`crm/followups/${this.followup.id}/complete`)
                        .then(() => {
                            this.$swal(
                                this.$t("Success"),
                                this.$t("Followup_completed_successfully"),
                                "success"
                            );
                            this.Get_Followup(this.followup.id);
                        })
                        .catch((error) => {
                            this.$swal(
                                this.$t("Error"),
                                error.response?.data?.message ||
                                    this.$t("Failed_to_complete_followup"),
                                "error"
                            );
                        })
                        .finally(() => {
                            NProgress.done();
                        });
                }
            });
        },
        //---------------------------------------- Remove Followup
        Remove_Followup() {
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
                        .delete(`crm/followups/${this.followup.id}`)
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
        //---------------------------------------- Format Methods
        formatDateTime(date) {
            if (!date) return "";
            return new Date(date).toLocaleString();
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
            this.Get_Followup(id);
        } else {
            this.$router.push({ name: "crm-followups" });
        }
    },
};
</script>
