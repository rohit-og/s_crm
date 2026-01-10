<template>
    <div class="main-content">
        <breadcumb :page="$t('CRM Dashboard')" :folder="$t('CRM')" />
        <div
            v-if="isLoading"
            class="loading_page spinner spinner-primary mr-3"
        ></div>
        <div v-else class="row">
            <!-- Statistics Cards -->
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div
                    class="card card-icon-bg card-icon-bg-primary o-hidden mb-4"
                >
                    <div class="card-body text-center">
                        <i class="i-File-Clipboard-File--Text text-32"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">
                                {{ $t("Total_Deals") }}
                            </p>
                            <p class="text-primary text-24 line-height-1 mb-2">
                                {{ stats.total_deals }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div
                    class="card card-icon-bg card-icon-bg-primary o-hidden mb-4"
                >
                    <div class="card-body text-center">
                        <i class="i-Add-File text-32"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">
                                {{ $t("Open_Deals") }}
                            </p>
                            <p class="text-success text-24 line-height-1 mb-2">
                                {{ stats.open_deals }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div
                    class="card card-icon-bg card-icon-bg-primary o-hidden mb-4"
                >
                    <div class="card-body text-center">
                        <i class="i-Calendar-3 text-32"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">
                                {{ $t("Scheduled_Followups") }}
                            </p>
                            <p class="text-warning text-24 line-height-1 mb-2">
                                {{ stats.scheduled_followups }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div
                    class="card card-icon-bg card-icon-bg-primary o-hidden mb-4"
                >
                    <div class="card-body text-center">
                        <i class="i-Administrator text-32"></i>
                        <div class="content">
                            <p class="text-muted mt-2 mb-0">
                                {{ $t("Total_Contacts") }}
                            </p>
                            <p class="text-info text-24 line-height-1 mb-2">
                                {{ stats.total_contacts }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Deals -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>{{ $t("Recent_Deals") }}</h5>
                    </div>
                    <div class="card-body">
                        <div
                            v-if="recentDeals.length === 0"
                            class="text-center text-muted py-4"
                        >
                            {{ $t("No_deals_found") }}
                        </div>
                        <div v-else>
                            <div
                                v-for="deal in recentDeals"
                                :key="deal.id"
                                class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom"
                            >
                                <div>
                                    <h6 class="mb-1">{{ deal.name }}</h6>
                                    <small class="text-muted">{{
                                        deal.client_name
                                    }}</small>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-primary">{{
                                        deal.stage_name
                                    }}</span>
                                    <p
                                        class="mb-0 mt-1 text-success font-weight-bold"
                                    >
                                        {{
                                            formatPriceWithSymbol(
                                                currentUser.currency,
                                                deal.value,
                                                2
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                            <router-link
                                to="/app/crm/deals"
                                class="btn btn-sm btn-primary btn-block mt-2"
                            >
                                {{ $t("View_All_Deals") }}
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Followups -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>{{ $t("Upcoming_Followups") }}</h5>
                    </div>
                    <div class="card-body">
                        <div
                            v-if="upcomingFollowups.length === 0"
                            class="text-center text-muted py-4"
                        >
                            {{ $t("No_followups_scheduled") }}
                        </div>
                        <div v-else>
                            <div
                                v-for="followup in upcomingFollowups"
                                :key="followup.id"
                                class="mb-3 pb-3 border-bottom"
                            >
                                <div
                                    class="d-flex justify-content-between align-items-start"
                                >
                                    <div>
                                        <h6 class="mb-1">
                                            {{ followup.subject }}
                                        </h6>
                                        <small class="text-muted">
                                            <i class="i-Calendar-3"></i>
                                            {{
                                                formatDate(
                                                    followup.scheduled_at
                                                )
                                            }}
                                        </small>
                                    </div>
                                    <span
                                        :class="
                                            'badge badge-' +
                                            getFollowupTypeColor(followup.type)
                                        "
                                    >
                                        {{ followup.type }}
                                    </span>
                                </div>
                            </div>
                            <router-link
                                to="/app/crm/followups"
                                class="btn btn-sm btn-primary btn-block mt-2"
                            >
                                {{ $t("View_All_Followups") }}
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";
import axios from "axios";

export default {
    name: "crm-dashboard",
    metaInfo: { title: "CRM Dashboard" },
    data() {
        return {
            isLoading: true,
            stats: {
                total_deals: 0,
                open_deals: 0,
                scheduled_followups: 0,
                total_contacts: 0,
            },
            recentDeals: [],
            upcomingFollowups: [],
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
        formatPriceWithSymbol(symbol, number, dec) {
            const safeSymbol = symbol || "";
            const value = Number(number || 0).toFixed(dec || 2);
            return safeSymbol ? `${safeSymbol} ${value}` : value;
        },
        formatDate(date) {
            if (!date) return "";
            return new Date(date).toLocaleDateString();
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
        async fetchDashboardData() {
            this.isLoading = true;
            NProgress.start();
            try {
                // Fetch stats (you may need to create a dashboard endpoint)
                const [dealsRes, followupsRes, contactsRes] = await Promise.all(
                    [
                        axios.get("crm/deals", {
                            params: {
                                limit: 5,
                                SortField: "created_at",
                                SortType: "desc",
                            },
                        }),
                        axios.get("crm/followups/scheduled", {
                            params: { limit: 5 },
                        }),
                        axios.get("crm/contacts", { params: { limit: 1 } }),
                    ]
                );

                this.recentDeals = (dealsRes.data.deals || []).map((deal) => ({
                    id: deal.id,
                    name: deal.name,
                    client_name: deal.client?.name || "",
                    stage_name: deal.stage?.name || "",
                    value: deal.value || 0,
                }));

                this.upcomingFollowups = followupsRes.data.followups || [];

                // Calculate stats
                this.stats.total_deals = dealsRes.data.totalRows || 0;
                this.stats.open_deals =
                    dealsRes.data.deals?.filter((d) => d.status === "open")
                        .length || 0;
                this.stats.scheduled_followups =
                    followupsRes.data.followups?.length || 0;
                this.stats.total_contacts = contactsRes.data.totalRows || 0;
            } catch (error) {
                console.error("Error fetching CRM dashboard data:", error);
            } finally {
                this.isLoading = false;
                NProgress.done();
            }
        },
    },
    created() {
        this.fetchDashboardData();
    },
};
</script>
