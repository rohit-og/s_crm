<template>
    <div class="pipeline-kanban">
        <div v-if="isLoading" class="text-center py-5">
            <div class="spinner spinner-primary"></div>
        </div>
        <div
            v-else-if="stages.length === 0"
            class="text-center py-5 text-muted"
        >
            <i class="i-File-Horizontal-Text text-32 mb-3"></i>
            <p>{{ $t("No_stages_available") }}</p>
        </div>
        <div v-else class="kanban-container">
            <div class="row">
                <div
                    v-for="stage in stages"
                    :key="stage.id"
                    class="col-md-3 mb-4"
                    @drop="onDrop($event, stage.id)"
                    @dragover.prevent
                    @dragenter.prevent
                    :class="{ 'drag-over': dragOverStage === stage.id }"
                    @dragenter="dragOverStage = stage.id"
                    @dragleave="dragOverStage = null"
                >
                    <div class="card h-100">
                        <div
                            class="card-header d-flex justify-content-between align-items-center"
                            :style="{
                                backgroundColor: stage.color,
                                color: '#fff',
                            }"
                        >
                            <div>
                                <h6 class="mb-0">{{ stage.name }}</h6>
                                <small
                                    >{{ getDealsByStage(stage.id).length }}
                                    {{ $t("deals") }}</small
                                >
                            </div>
                            <div
                                class="stage-value"
                                v-if="getStageValue(stage.id) > 0"
                            >
                                <small>
                                    {{
                                        formatPriceWithSymbol(
                                            currency || currentUser.currency,
                                            getStageValue(stage.id),
                                            2
                                        )
                                    }}
                                </small>
                            </div>
                        </div>
                        <div
                            class="card-body p-2"
                            style="
                                min-height: 400px;
                                max-height: 600px;
                                overflow-y: auto;
                            "
                        >
                            <draggable
                                :list="getDealsByStage(stage.id)"
                                :group="{
                                    name: 'deals',
                                    pull: true,
                                    put: true,
                                }"
                                @end="onDragEnd"
                                :move="onMove"
                                :disabled="!canEdit"
                                item-key="id"
                                handle=".deal-card"
                            >
                                <template #item="{ element: deal }">
                                    <DealCard
                                        :deal="deal"
                                        :currency="currency"
                                        :can-edit="canEdit"
                                        :can-delete="canDelete"
                                        :can-view="canView"
                                        @delete="handleDelete"
                                        @dragstart="draggedDealId = deal.id"
                                    />
                                </template>
                            </draggable>
                            <div
                                v-if="getDealsByStage(stage.id).length === 0"
                                class="text-center text-muted py-4"
                            >
                                <i
                                    class="i-File-Clipboard-File--Text text-32 mb-2"
                                ></i>
                                <p class="mb-0">{{ $t("Drop_deals_here") }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import draggable from "vuedraggable";
import DealCard from "./DealCard.vue";
import NProgress from "nprogress";
import axios from "axios";

export default {
    name: "crm-pipeline-kanban",
    components: {
        draggable,
        DealCard,
    },
    props: {
        pipelineId: {
            type: [String, Number],
            required: true,
        },
        deals: {
            type: Array,
            default: () => [],
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
            isLoading: false,
            stages: [],
            draggedDealId: null,
            dragOverStage: null,
        };
    },
    computed: {
        ...mapGetters(["currentUserPermissions", "currentUser"]),
    },
    methods: {
        //---------------------------------------- Get Deals By Stage
        getDealsByStage(stageId) {
            return this.deals.filter((d) => d.pipeline_stage_id === stageId);
        },
        //---------------------------------------- Get Stage Value
        getStageValue(stageId) {
            return this.getDealsByStage(stageId).reduce(
                (sum, deal) => sum + (parseFloat(deal.value) || 0),
                0
            );
        },
        //---------------------------------------- Fetch Stages
        async Fetch_Stages() {
            this.isLoading = true;
            try {
                const response = await axios.get(
                    `crm/pipelines/${this.pipelineId}/stages`
                );
                this.stages = response.data.stages || response.data.data || [];
                // Sort by sort_order
                this.stages.sort(
                    (a, b) => (a.sort_order || 0) - (b.sort_order || 0)
                );
            } catch (error) {
                console.error("Error fetching stages:", error);
                this.stages = [];
            } finally {
                this.isLoading = false;
            }
        },
        //---------------------------------------- On Move
        onMove(event) {
            // Allow move if editing is enabled
            return this.canEdit;
        },
        //---------------------------------------- On Drag End
        async onDragEnd(event) {
            const dealId =
                event.item.getAttribute("data-id") || this.draggedDealId;
            const newStageId = parseInt(
                event.to.dataset.stageId ||
                    event.to.closest("[data-stage-id]")?.dataset.stageId
            );

            if (!dealId || !newStageId) return;

            const deal = this.deals.find((d) => d.id === parseInt(dealId));
            if (!deal || deal.pipeline_stage_id === newStageId) {
                this.draggedDealId = null;
                return;
            }

            await this.Move_Deal_To_Stage(dealId, newStageId);
            this.draggedDealId = null;
            this.dragOverStage = null;
        },
        //---------------------------------------- On Drop (fallback for native drag)
        async onDrop(event, stageId) {
            event.preventDefault();
            const dealId = event.dataTransfer.getData("dealId");
            if (dealId) {
                const deal = this.deals.find((d) => d.id === parseInt(dealId));
                if (deal && deal.pipeline_stage_id !== stageId) {
                    await this.Move_Deal_To_Stage(dealId, stageId);
                }
            }
            this.dragOverStage = null;
        },
        //---------------------------------------- Move Deal To Stage
        async Move_Deal_To_Stage(dealId, stageId) {
            if (!this.canEdit) return;

            NProgress.start();
            try {
                await axios.post(`crm/deals/${dealId}/move-stage`, {
                    pipeline_stage_id: stageId,
                });

                // Update deal locally
                const dealIndex = this.deals.findIndex(
                    (d) => d.id === parseInt(dealId)
                );
                if (dealIndex !== -1) {
                    this.deals[dealIndex].pipeline_stage_id = stageId;
                }

                this.$emit("deal-moved", { dealId, stageId });
                this.makeToast(
                    "success",
                    this.$t("Deal_moved_successfully"),
                    this.$t("Success")
                );
            } catch (error) {
                this.makeToast(
                    "danger",
                    error.response?.data?.message ||
                        this.$t("Failed_to_move_deal"),
                    this.$t("Error")
                );
                // Reload deals to reset state
                this.$emit("reload-deals");
            } finally {
                NProgress.done();
            }
        },
        //---------------------------------------- Handle Delete
        handleDelete(dealId) {
            this.$emit("deal-deleted", dealId);
        },
        //---------------------------------------- Format Methods
        formatPriceWithSymbol(symbol, number, dec) {
            const safeSymbol = symbol || "";
            const value = Number(number || 0).toFixed(dec || 2);
            return safeSymbol ? `${safeSymbol} ${value}` : value;
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
        await this.Fetch_Stages();
    },
    watch: {
        pipelineId() {
            if (this.pipelineId) {
                this.Fetch_Stages();
            }
        },
    },
};
</script>

<style scoped>
.pipeline-kanban {
    width: 100%;
}

.kanban-container {
    overflow-x: auto;
    padding-bottom: 10px;
}

.kanban-container .row {
    flex-wrap: nowrap;
    min-width: fit-content;
}

.kanban-container .col-md-3 {
    min-width: 280px;
}

.drag-over {
    background-color: rgba(0, 123, 255, 0.1);
    border: 2px dashed #007bff;
    border-radius: 4px;
}

.stage-value {
    background-color: rgba(255, 255, 255, 0.2);
    padding: 2px 8px;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .kanban-container .col-md-3 {
        min-width: 250px;
    }
}
</style>
