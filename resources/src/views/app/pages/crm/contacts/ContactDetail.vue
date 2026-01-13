<template>
    <div class="main-content">
        <breadcumb :page="$t('Contact_Details')" :folder="$t('CRM')" />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <div v-else-if="!contact" class="alert alert-warning">
            <h5>{{ $t("Error") }}</h5>
            <p>{{ $t("Contact_not_found_or_failed_to_load") }}</p>
            <b-button
                variant="primary"
                @click="$router.push({ name: 'crm-contacts' })"
            >
                {{ $t("Back_to_Contacts") }}
            </b-button>
        </div>
        <div v-else-if="contact">
            <!-- Contact Header -->
            <b-card class="mb-4 shadow-sm">
                <b-row class="align-items-center">
                    <b-col md="8">
                        <h4 class="mb-2">
                            <i class="i-User mr-2 text-primary"></i>
                            {{ contact.name }}
                        </h4>
                        <div class="text-muted">
                            <span class="mr-3" v-if="contact.email"
                                ><strong>{{ $t("Email") }}:</strong>
                                {{ contact.email }}</span
                            >
                            <span class="mr-3"
                                ><strong>{{ $t("Phone") }}:</strong>
                                <a
                                    v-if="contact.phone"
                                    :href="'tel:' + contact.phone"
                                    class="text-primary ml-1"
                                >
                                    <i class="i-Phone mr-1"></i>
                                    {{ contact.phone }}
                                </a>
                                <span v-else class="text-muted ml-1">
                                    {{ $t("Not_Provided") }}
                                </span>
                            </span>
                            <span class="mr-3" v-if="contact.company_name"
                                ><strong>{{ $t("Company") }}:</strong>
                                {{ contact.company_name }}</span
                            >
                            <span class="mr-3" v-if="contact.assigned_agent"
                                ><strong>{{ $t("Assigned_Agent") }}:</strong>
                                {{ contact.assigned_agent.name }}</span
                            >
                        </div>
                        <div
                            class="mt-2"
                            v-if="contact.tags && contact.tags.length > 0"
                        >
                            <span
                                v-for="tag in contact.tags"
                                :key="tag.id"
                                class="badge mr-1 mb-1"
                                :style="{
                                    backgroundColor: tag.color,
                                    color: '#fff',
                                }"
                            >
                                {{ tag.name }}
                            </span>
                        </div>
                        <div
                            class="mt-2"
                            v-if="
                                contact.contact_groups &&
                                contact.contact_groups.length > 0
                            "
                        >
                            <span
                                v-for="group in contact.contact_groups"
                                :key="group.id"
                                class="badge badge-info mr-1 mb-1"
                                :style="{
                                    backgroundColor: group.color,
                                    color: '#fff',
                                }"
                            >
                                {{ group.name }}
                            </span>
                        </div>
                    </b-col>
                    <b-col md="4" class="text-right">
                        <b-button-group>
                            <b-button
                                variant="secondary"
                                size="sm"
                                @click="$router.push({ name: 'crm-contacts' })"
                            >
                                <i class="i-Left"></i>
                                {{ $t("Back") }}
                            </b-button>
                            <b-button
                                v-if="can('crm_contacts_assign_agent')"
                                variant="outline-primary"
                                size="sm"
                                @click="Show_Assign_Modal"
                            >
                                <i class="i-Administrator"></i>
                                {{ $t("Assign_Agent") }}
                            </b-button>
                            <b-button
                                variant="primary"
                                size="sm"
                                @click="Show_Manage_Groups_Modal"
                            >
                                <i class="i-Folder"></i>
                                {{ $t("Groups") }}
                            </b-button>
                            <b-button
                                variant="info"
                                size="sm"
                                @click="Show_Manage_Tags_Modal"
                            >
                                <i class="i-Price-Tag"></i>
                                {{ $t("Tags") }}
                            </b-button>
                        </b-button-group>
                    </b-col>
                </b-row>
            </b-card>

            <!-- Summary Cards -->
            <b-row class="mb-4">
                <b-col lg="3" md="6" sm="12" class="mb-3">
                    <b-card
                        class="text-center shadow-sm border-left-primary h-100"
                    >
                        <div class="mb-2">
                            <i
                                class="i-File-Clipboard-File--Text text-primary"
                                style="font-size: 2.5rem"
                            ></i>
                        </div>
                        <h6 class="text-muted mb-2">{{ $t("Total_Deals") }}</h6>
                        <h3 class="mb-0 text-primary">
                            {{ stats.total_deals }}
                        </h3>
                    </b-card>
                </b-col>
                <b-col lg="3" md="6" sm="12" class="mb-3">
                    <b-card
                        class="text-center shadow-sm border-left-success h-100"
                    >
                        <div class="mb-2">
                            <i
                                class="i-Check text-success"
                                style="font-size: 2.5rem"
                            ></i>
                        </div>
                        <h6 class="text-muted mb-2">{{ $t("Won_Deals") }}</h6>
                        <h3 class="mb-0 text-success">{{ stats.won_deals }}</h3>
                    </b-card>
                </b-col>
                <b-col lg="3" md="6" sm="12" class="mb-3">
                    <b-card
                        class="text-center shadow-sm border-left-info h-100"
                    >
                        <div class="mb-2">
                            <i
                                class="i-Calendar-3 text-info"
                                style="font-size: 2.5rem"
                            ></i>
                        </div>
                        <h6 class="text-muted mb-2">
                            {{ $t("Total_Followups") }}
                        </h6>
                        <h3 class="mb-0 text-info">
                            {{ stats.total_followups }}
                        </h3>
                    </b-card>
                </b-col>
                <b-col lg="3" md="6" sm="12" class="mb-3">
                    <b-card
                        class="text-center shadow-sm border-left-warning h-100"
                    >
                        <div class="mb-2">
                            <i
                                class="i-Dollar text-warning"
                                style="font-size: 2.5rem"
                            ></i>
                        </div>
                        <h6 class="text-muted mb-2">{{ $t("Total_Value") }}</h6>
                        <h3 class="mb-0 text-warning">
                            {{
                                formatPriceWithSymbol(
                                    (currentUser && currentUser.currency) || "",
                                    stats.total_value,
                                    2
                                )
                            }}
                        </h3>
                    </b-card>
                </b-col>
            </b-row>

            <b-row>
                <!-- Contact Information -->
                <b-col md="8">
                    <b-card class="mb-4">
                        <h5 class="mb-3">
                            <i class="i-Information mr-2"></i
                            >{{ $t("Contact_Information") }}
                        </h5>
                        <table class="table table-hover table-bordered">
                            <tbody>
                                <tr>
                                    <td width="40%">{{ $t("Name") }}</td>
                                    <th>{{ contact.name }}</th>
                                </tr>
                                <tr v-if="contact.email">
                                    <td>{{ $t("Email") }}</td>
                                    <th>{{ contact.email }}</th>
                                </tr>
                                <tr>
                                    <td>{{ $t("Phone") }}</td>
                                    <th>
                                        <a
                                            v-if="contact.phone"
                                            :href="'tel:' + contact.phone"
                                            class="text-primary"
                                        >
                                            <i class="i-Phone mr-1"></i>
                                            {{ contact.phone }}
                                        </a>
                                        <span v-else class="text-muted">
                                            {{ $t("Not_Provided") }}
                                        </span>
                                    </th>
                                </tr>
                                <tr v-if="contact.company_name">
                                    <td>{{ $t("Company") }}</td>
                                    <th>{{ contact.company_name }}</th>
                                </tr>
                                <tr v-if="contact.job_title">
                                    <td>{{ $t("Job_Title") }}</td>
                                    <th>{{ contact.job_title }}</th>
                                </tr>
                                <tr v-if="contact.source">
                                    <td>{{ $t("Source") }}</td>
                                    <th>
                                        <span class="badge badge-info">{{
                                            contact.source
                                        }}</span>
                                    </th>
                                </tr>
                                <tr v-if="contact.assigned_agent">
                                    <td>{{ $t("Assigned_Agent") }}</td>
                                    <th>{{ contact.assigned_agent.name }}</th>
                                </tr>
                                <tr>
                                    <td>{{ $t("Created_At") }}</td>
                                    <th>
                                        {{ formatDateTime(contact.created_at) }}
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    </b-card>

                    <!-- Tabs for Deals and Followups -->
                    <b-card>
                        <b-tabs v-model="activeTab" lazy>
                            <!-- Deals Tab -->
                            <b-tab :title="$t('Deals')">
                                <div class="mb-3">
                                    <b-button
                                        v-if="can('crm_deals_add')"
                                        variant="primary"
                                        size="sm"
                                        @click="New_Deal"
                                    >
                                        <i class="i-Add"></i>
                                        {{ $t("Add_Deal") }}
                                    </b-button>
                                </div>
                                <div
                                    v-if="deals.length === 0"
                                    class="text-center text-muted py-4"
                                >
                                    {{ $t("No_deals_found") }}
                                </div>
                                <div v-else>
                                    <div
                                        v-for="deal in deals"
                                        :key="deal.id"
                                        class="border-bottom pb-3 mb-3"
                                    >
                                        <div
                                            class="d-flex justify-content-between align-items-start"
                                        >
                                            <div>
                                                <h6 class="mb-1">
                                                    <router-link
                                                        :to="{
                                                            name: 'crm_deal_detail',
                                                            params: {
                                                                id: deal.id,
                                                            },
                                                        }"
                                                        >{{
                                                            deal.name
                                                        }}</router-link
                                                    >
                                                </h6>
                                                <small class="text-muted">
                                                    <span
                                                        class="badge ml-1"
                                                        :style="{
                                                            backgroundColor:
                                                                deal.stage
                                                                    ?.color,
                                                            color: '#fff',
                                                        }"
                                                    >
                                                        {{ deal.stage?.name }}
                                                    </span>
                                                    <span
                                                        :class="
                                                            'badge badge-' +
                                                            getStatusBadge(
                                                                deal.status
                                                            )
                                                        "
                                                        class="ml-1"
                                                    >
                                                        {{ deal.status }}
                                                    </span>
                                                </small>
                                                <p class="mb-0 mt-1">
                                                    <strong
                                                        class="text-success"
                                                    >
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
                                                    </strong>
                                                    <span
                                                        class="text-muted ml-2"
                                                        >{{ deal.probability }}%
                                                        probability</span
                                                    >
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </b-tab>

                            <!-- Followups Tab -->
                            <b-tab :title="$t('Followups')">
                                <div class="mb-3">
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
                                                            params: {
                                                                id: followup.id,
                                                            },
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
                                                    getFollowupTypeColor(
                                                        followup.type
                                                    )
                                                "
                                            >
                                                {{ followup.type }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </b-tab>
                        </b-tabs>
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
                            v-if="can('crm_deals_add')"
                            variant="primary"
                            size="sm"
                            block
                            class="mb-2"
                            @click="New_Deal"
                        >
                            <i class="i-Add"></i>
                            {{ $t("Create_Deal") }}
                        </b-button>
                        <b-button
                            v-if="can('crm_followups_add')"
                            variant="success"
                            size="sm"
                            block
                            class="mb-2"
                            @click="New_Followup"
                        >
                            <i class="i-Add"></i>
                            {{ $t("Create_Followup") }}
                        </b-button>
                        <b-button
                            v-if="can('crm_contacts_assign_agent')"
                            variant="outline-primary"
                            size="sm"
                            block
                            class="mb-2"
                            @click="Show_Assign_Modal"
                        >
                            <i class="i-Administrator"></i>
                            {{ $t("Assign_Agent") }}
                        </b-button>
                        <b-button
                            variant="outline-info"
                            size="sm"
                            block
                            class="mb-2"
                            @click="Show_Manage_Groups_Modal"
                        >
                            <i class="i-Folder"></i>
                            {{ $t("Manage_Groups") }}
                        </b-button>
                        <b-button
                            variant="outline-warning"
                            size="sm"
                            block
                            class="mb-2"
                            @click="Show_Manage_Tags_Modal"
                        >
                            <i class="i-Price-Tag"></i>
                            {{ $t("Manage_Tags") }}
                        </b-button>
                    </b-card>
                </b-col>
            </b-row>

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

            <!-- Manage Groups Modal -->
            <ContactGroupManager
                v-if="contact"
                :contact-id="contact.id"
                :current-groups="contact.contact_groups || []"
                @updated="Get_Contact"
            />

            <!-- Manage Tags Modal -->
            <ContactTagManager
                v-if="contact"
                :contact-id="contact.id"
                :current-tags="contact.tags || []"
                @updated="Get_Contact"
            />
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";
// Use window.axios which has baseURL configured in main.js
import ContactGroupManager from "../../../../../components/crm/ContactGroupManager.vue";
import ContactTagManager from "../../../../../components/crm/ContactTagManager.vue";

export default {
    name: "crm-contact-detail",
    components: {
        ContactGroupManager,
        ContactTagManager,
    },
    metaInfo: {
        title: "Contact Details",
    },
    data() {
        return {
            isLoading: true,
            contact: null,
            deals: [],
            followups: [],
            agents: [],
            stats: {
                total_deals: 0,
                won_deals: 0,
                total_followups: 0,
                total_value: 0,
            },
            activeTab: 0,
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
        //---------------------------------------- Get Contact Data
        async Get_Contact(id) {
            NProgress.start();
            try {
                const [contactRes, followupsRes] = await Promise.all([
                    window.axios.get(`crm/contacts/${id}`),
                    window.axios
                        .get(`crm/clients/${id}/followups`)
                        .catch(() => ({
                            data: { followups: [] },
                        })),
                ]);

                // ContactController show() returns the contact directly, not wrapped
                this.contact =
                    contactRes.data.contact ||
                    contactRes.data.data ||
                    contactRes.data;

                // Ensure contact is not null before proceeding
                if (!this.contact) {
                    throw new Error("Contact not found");
                }

                // Contact model has deals relationship loaded from ContactController
                // Use it if available, otherwise fetch separately
                if (this.contact.deals && Array.isArray(this.contact.deals)) {
                    this.deals = this.contact.deals;
                } else {
                    // Fallback: fetch deals by filtering (if DealController supports it)
                    // For now, use empty array since relationship should be loaded
                    this.deals = [];
                }

                // Contact model has followups relationship loaded from ContactController
                // Use it if available, otherwise use the separate API call
                if (
                    this.contact.followups &&
                    Array.isArray(this.contact.followups)
                ) {
                    this.followups = this.contact.followups;
                } else {
                    this.followups =
                        followupsRes.data.followups ||
                        followupsRes.data.data ||
                        [];
                }

                // Calculate stats
                this.stats.total_deals = this.deals.length;
                this.stats.won_deals = this.deals.filter(
                    (d) => d.status === "closed_won"
                ).length;
                this.stats.total_followups = this.followups.length;
                this.stats.total_value = this.deals.reduce(
                    (sum, deal) => sum + (parseFloat(deal.value) || 0),
                    0
                );

                // Fetch agents
                await this.Fetch_Agents();

                this.isLoading = false;
                NProgress.done();
            } catch (error) {
                console.error("Error loading contact:", error);
                this.makeToast(
                    "danger",
                    error.response?.data?.message ||
                        error.message ||
                        this.$t("Failed_to_load_contact"),
                    this.$t("Error")
                );
                this.contact = null; // Ensure contact is null so error message shows
                this.isLoading = false;
                NProgress.done();
                // Don't auto-redirect, let user see the error and choose to go back
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
        //---------------------------------------- Show Assign Modal
        Show_Assign_Modal() {
            if (!this.contact) return;
            this.assignForm.agent_id = this.contact.assigned_agent_id;
            this.$bvModal.show("assignModal");
        },
        //---------------------------------------- Assign Agent
        Assign_Agent(bvModalEvt) {
            bvModalEvt.preventDefault();
            if (!this.contact || !this.contact.id) {
                this.makeToast(
                    "danger",
                    this.$t("Contact_not_loaded"),
                    this.$t("Error")
                );
                return;
            }
            NProgress.start();
            window.axios
                .post(`crm/contacts/${this.contact.id}/assign-agent`, {
                    agent_id: this.assignForm.agent_id,
                })
                .then(() => {
                    this.$swal(
                        this.$t("Success"),
                        this.$t("Agent_assigned_successfully"),
                        "success"
                    );
                    this.$bvModal.hide("assignModal");
                    this.Get_Contact(this.contact.id);
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
        //---------------------------------------- Show Manage Groups Modal
        Show_Manage_Groups_Modal() {
            this.$bvModal.show("contactGroupModal");
        },
        //---------------------------------------- Show Manage Tags Modal
        Show_Manage_Tags_Modal() {
            this.$bvModal.show("contactTagModal");
        },
        //---------------------------------------- New Deal
        New_Deal() {
            if (!this.contact || !this.contact.id) {
                this.makeToast(
                    "danger",
                    this.$t("Contact_not_loaded"),
                    this.$t("Error")
                );
                return;
            }
            this.$router.push({
                name: "crm_deal_create",
                query: { client_id: this.contact.id },
            });
        },
        //---------------------------------------- New Followup
        New_Followup() {
            if (!this.contact || !this.contact.id) {
                this.makeToast(
                    "danger",
                    this.$t("Contact_not_loaded"),
                    this.$t("Error")
                );
                return;
            }
            this.$router.push({
                name: "crm-followup-create",
                query: { client_id: this.contact.id },
            });
        },
        //---------------------------------------- Format Methods
        formatPriceWithSymbol(symbol, number, dec) {
            const safeSymbol = symbol || "";
            const value = Number(number || 0).toFixed(dec || 2);
            return safeSymbol ? `${safeSymbol} ${value}` : value;
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
            this.Get_Contact(id);
        } else {
            this.$router.push({ name: "crm-contacts" });
        }
    },
};
</script>
