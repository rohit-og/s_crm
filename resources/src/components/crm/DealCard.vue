<template>
    <div
        class="card mb-2 deal-card"
        :class="{ 'deal-card-dragging': isDragging }"
        draggable="true"
        @dragstart="onDragStart"
        @dragend="onDragEnd"
    >
        <div class="card-body p-2">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="card-title mb-0 flex-grow-1">
                    <router-link
                        :to="{
                            name: 'crm_deal_detail',
                            params: { id: deal.id },
                        }"
                        class="text-dark"
                        >{{ deal.name }}</router-link
                    >
                </h6>
                <b-dropdown
                    variant="link"
                    right
                    no-caret
                    class="deal-actions"
                    v-if="canEdit || canDelete"
                >
                    <template #button-content>
                        <i class="i-More-Vertical text-muted"></i>
                    </template>
                    <b-dropdown-item
                        v-if="canView"
                        :to="{
                            name: 'crm_deal_detail',
                            params: { id: deal.id },
                        }"
                    >
                        <i class="i-Eye mr-2"></i>
                        {{ $t("View") }}
                    </b-dropdown-item>
                    <b-dropdown-item
                        v-if="canEdit"
                        :to="{
                            name: 'crm_deal_edit',
                            params: { id: deal.id },
                        }"
                    >
                        <i class="i-Edit mr-2"></i>
                        {{ $t("Edit") }}
                    </b-dropdown-item>
                    <b-dropdown-divider v-if="canDelete"></b-dropdown-divider>
                    <b-dropdown-item
                        v-if="canDelete"
                        @click="$emit('delete', deal.id)"
                        class="text-danger"
                    >
                        <i class="i-Close-Window mr-2"></i>
                        {{ $t("Delete") }}
                    </b-dropdown-item>
                </b-dropdown>
            </div>

            <small class="text-muted d-block mb-2" v-if="deal.client_name">
                <i class="i-User mr-1"></i>
                {{ deal.client_name }}
            </small>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="font-weight-bold text-success">
                    {{
                        formatPriceWithSymbol(
                            currency || currentUser.currency,
                            deal.value,
                            2
                        )
                    }}
                </span>
                <span
                    class="badge badge-primary"
                    v-if="deal.probability !== null"
                    >{{ deal.probability }}%</span
                >
            </div>

            <div class="mb-2" v-if="deal.assigned_user">
                <small class="text-muted">
                    <i class="i-Administrator mr-1"></i>
                    {{ deal.assigned_user.name }}
                </small>
            </div>

            <div v-if="deal.expected_close_date" class="mb-2">
                <small class="text-muted">
                    <i class="i-Calendar-3 mr-1"></i>
                    {{ formatDate(deal.expected_close_date) }}
                </small>
            </div>

            <router-link
                :to="{
                    name: 'crm_deal_detail',
                    params: { id: deal.id },
                }"
                class="btn btn-sm btn-outline-primary btn-block mt-2"
            >
                {{ $t("View") }}
            </router-link>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

export default {
    name: "crm-deal-card",
    props: {
        deal: {
            type: Object,
            required: true,
        },
        currency: {
            type: String,
            default: null,
        },
        canEdit: {
            type: Boolean,
            default: true,
        },
        canDelete: {
            type: Boolean,
            default: true,
        },
        canView: {
            type: Boolean,
            default: true,
        },
    },
    data() {
        return {
            isDragging: false,
        };
    },
    computed: {
        ...mapGetters(["currentUserPermissions", "currentUser"]),
    },
    methods: {
        //---------------------------------------- On Drag Start
        onDragStart(event) {
            this.isDragging = true;
            event.dataTransfer.setData("dealId", this.deal.id);
            event.dataTransfer.effectAllowed = "move";
            this.$emit("dragstart", this.deal.id);
        },
        //---------------------------------------- On Drag End
        onDragEnd() {
            this.isDragging = false;
            this.$emit("dragend");
        },
        //---------------------------------------- Format Methods
        formatPriceWithSymbol(symbol, number, dec) {
            const safeSymbol = symbol || "";
            const value = Number(number || 0).toFixed(dec || 2);
            return safeSymbol ? `${safeSymbol} ${value}` : value;
        },
        formatDate(date) {
            if (!date) return "";
            return new Date(date).toLocaleDateString();
        },
    },
};
</script>

<style scoped>
.deal-card {
    cursor: move;
    transition: all 0.2s ease;
}

.deal-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.deal-card-dragging {
    opacity: 0.5;
    transform: rotate(2deg);
}

.deal-actions {
    margin-top: -5px;
}

.deal-card .card-title a:hover {
    text-decoration: none;
    color: #007bff !important;
}
</style>
