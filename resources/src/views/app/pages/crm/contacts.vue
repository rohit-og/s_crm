<template>
    <div class="main-content">
        <breadcumb :page="$t('CRM Contacts')" :folder="$t('CRM')" />
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
                    @click="contacts_PDF()"
                    size="sm"
                    variant="outline-success m-1"
                >
                    <i class="i-File-Copy"></i> PDF
                </b-button>
            </div>

            <vue-good-table
                mode="remote"
                :columns="columns"
                :totalRows="totalRows"
                :rows="contacts"
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
                    <span v-if="props.column.field === 'assigned_agent'">
                        {{ props.row.assigned_agent?.name || $t("Unassigned") }}
                    </span>
                    <span v-else-if="props.column.field === 'tags'">
                        <span
                            v-for="tag in props.row.tags"
                            :key="tag.id"
                            class="badge badge-primary mr-1"
                            :style="{ backgroundColor: tag.color }"
                        >
                            {{ tag.name }}
                        </span>
                    </span>
                    <span v-else-if="props.column.field === 'groups'">
                        <span
                            v-for="group in props.row.contact_groups"
                            :key="group.id"
                            class="badge badge-info mr-1"
                            :style="{ backgroundColor: group.color }"
                        >
                            {{ group.name }}
                        </span>
                    </span>
                    <span v-else-if="props.column.field === 'actions'">
                        <router-link
                            v-if="can('crm_contacts_view')"
                            v-b-tooltip.hover
                            title="View"
                            :to="{
                                name: 'customer_details',
                                params: { id: props.row.id },
                            }"
                        >
                            <i class="i-Eye text-25 text-info"></i>
                        </router-link>
                        <a
                            v-if="can('crm_contacts_assign_agent')"
                            @click="Assign_Agent(props.row)"
                            v-b-tooltip.hover
                            title="Assign Agent"
                            class="cursor-pointer ml-2"
                        >
                            <i class="i-Administrator text-25 text-warning"></i>
                        </a>
                        <a
                            v-if="can('crm_contacts_add')"
                            @click="Add_To_Group(props.row)"
                            v-b-tooltip.hover
                            title="Add to Group"
                            class="cursor-pointer ml-2"
                        >
                            <i class="i-Folder text-25 text-success"></i>
                        </a>
                        <a
                            v-if="can('crm_contacts_add')"
                            @click="Manage_Tags(props.row)"
                            v-b-tooltip.hover
                            title="Manage Tags"
                            class="cursor-pointer ml-2"
                        >
                            <i class="i-Price-Tag text-25 text-primary"></i>
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
                    <b-form-group :label="$t('Source')">
                        <v-select
                            v-model="filterSource"
                            :options="sourceOptions"
                            :placeholder="$t('Select_Source')"
                            @input="Get_Contacts(serverParams.page)"
                        />
                    </b-form-group>
                    <b-form-group :label="$t('Assigned_Agent')">
                        <v-select
                            v-model="filterAgent"
                            :options="agents"
                            label="name"
                            :reduce="(option) => option.id"
                            :placeholder="$t('Select_Agent')"
                            @input="Get_Contacts(serverParams.page)"
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
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

export default {
    name: "crm-contacts",
    metaInfo: { title: "CRM Contacts" },
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
            contacts: [],
            filterSource: null,
            filterAgent: null,
            agents: [],
            sourceOptions: [
                { label: "Website", value: "website" },
                { label: "Referral", value: "referral" },
                { label: "Cold Call", value: "cold_call" },
                { label: "Email", value: "email" },
                { label: "Social Media", value: "social_media" },
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
                    label: this.$t("Name"),
                    field: "name",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Email"),
                    field: "email",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Phone"),
                    field: "phone",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Company"),
                    field: "company_name",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Source"),
                    field: "source",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Assigned_Agent"),
                    field: "assigned_agent",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Tags"),
                    field: "tags",
                    tdClass: "text-left",
                    thClass: "text-left",
                },
                {
                    label: this.$t("Groups"),
                    field: "groups",
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
        updateParams(newProps) {
            this.serverParams = Object.assign({}, this.serverParams, newProps);
        },
        onPageChange({ currentPage }) {
            if (this.serverParams.page !== currentPage) {
                this.updateParams({ page: currentPage });
                this.Get_Contacts(currentPage);
            }
        },
        onPerPageChange({ currentPerPage }) {
            if (this.limit !== currentPerPage) {
                this.limit = currentPerPage;
                this.updateParams({ page: 1, perPage: currentPerPage });
                this.Get_Contacts(1);
            }
        },
        onSortChange(params) {
            this.updateParams({
                sort: { type: params[0].type, field: params[0].field },
            });
            this.Get_Contacts(this.serverParams.page);
        },
        onSearch(value) {
            this.search = value.searchTerm;
            this.Get_Contacts(this.serverParams.page);
        },
        Reset_Filter() {
            this.filterSource = null;
            this.filterAgent = null;
            this.Get_Contacts(this.serverParams.page);
        },
        async Get_Contacts(page) {
            NProgress.start();
            NProgress.set(0.1);
            const params = {
                page,
                SortField: this.serverParams.sort.field,
                SortType: this.serverParams.sort.type,
                search: this.search || "",
                limit: this.limit,
            };
            if (this.filterSource) params.source = this.filterSource;
            if (this.filterAgent) params.assigned_agent_id = this.filterAgent;

            try {
                const response = await axios.get("crm/contacts", { params });
                this.contacts =
                    response.data.contacts || response.data.data || [];
                this.totalRows =
                    response.data.totalRows || response.data.total || 0;
            } catch (error) {
                console.error("Error fetching contacts:", error);
            } finally {
                NProgress.done();
                this.isLoading = false;
            }
        },
        async Fetch_Agents() {
            try {
                const response = await axios.get("users", {
                    params: { role: "crm_agent", limit: -1 },
                });
                this.agents = response.data.users || response.data.data || [];
            } catch (error) {
                console.error("Error fetching agents:", error);
            }
        },
        Assign_Agent(contact) {
            this.$swal({
                title: this.$t("Assign_Agent"),
                input: "select",
                inputOptions: this.agents.reduce((acc, agent) => {
                    acc[agent.id] = agent.name;
                    return acc;
                }, {}),
                showCancelButton: true,
                confirmButtonText: this.$t("Assign"),
                cancelButtonText: this.$t("Cancel"),
            }).then((result) => {
                if (result.value) {
                    NProgress.start();
                    axios
                        .post(`crm/contacts/${contact.id}/assign-agent`, {
                            agent_id: result.value,
                        })
                        .then(() => {
                            this.$swal(
                                this.$t("Success"),
                                this.$t("Agent_assigned_successfully"),
                                "success"
                            );
                            this.Get_Contacts(this.serverParams.page);
                        })
                        .catch(() => {
                            this.$swal(
                                this.$t("Error"),
                                this.$t("Failed_to_assign_agent"),
                                "error"
                            );
                        })
                        .finally(() => {
                            NProgress.done();
                        });
                }
            });
        },
        Add_To_Group(contact) {
            // This would open a modal to select groups
            this.$swal(this.$t("Feature_coming_soon"), "", "info");
        },
        Manage_Tags(contact) {
            // This would open a modal to manage tags
            this.$swal(this.$t("Feature_coming_soon"), "", "info");
        },
        contacts_PDF() {
            const pdf = new jsPDF("p", "pt");
            const headers = [
                this.$t("ID"),
                this.$t("Name"),
                this.$t("Email"),
                this.$t("Phone"),
                this.$t("Company"),
            ];
            const body = this.contacts.map((c) => [
                c.id,
                c.name,
                c.email || "",
                c.phone || "",
                c.company_name || "",
            ]);

            autoTable(pdf, {
                head: [headers],
                body,
                startY: 110,
                theme: "striped",
                styles: { fontSize: 9, cellPadding: 4 },
            });

            pdf.save("CRM_Contacts.pdf");
        },
    },
    async created() {
        await this.Fetch_Agents();
        this.Get_Contacts(1);
    },
};
</script>
