<template>
    <div class="main-content">
        <breadcumb :page="$t('Pipeline_Stages')" :folder="$t('CRM')" />
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
                            {{ pipeline?.name || $t("Pipeline_Stages") }}
                        </h4>
                        <small class="text-muted" v-if="pipeline">{{
                            pipeline.description
                        }}</small>
                    </b-col>
                    <b-col md="4" class="text-right">
                        <b-button
                            @click="New_Stage()"
                            variant="primary"
                            size="sm"
                        >
                            <i class="i-Add"></i>
                            {{ $t("Add_Stage") }}
                        </b-button>
                        <b-button
                            variant="secondary"
                            size="sm"
                            class="ml-2"
                            @click="$router.push({ name: 'crm-pipelines' })"
                        >
                            <i class="i-Left"></i>
                            {{ $t("Back") }}
                        </b-button>
                    </b-col>
                </b-row>
            </b-card>

            <!-- Stages List with Drag and Drop -->
            <b-card v-if="stages.length > 0">
                <div class="mb-3">
                    <small class="text-muted">{{
                        $t("Drag_and_drop_to_reorder_stages")
                    }}</small>
                </div>
                <draggable
                    v-model="stages"
                    @end="onDragEnd"
                    handle=".drag-handle"
                    animation="200"
                >
                    <div
                        v-for="(stage, index) in stages"
                        :key="stage.id"
                        class="stage-item mb-3 p-3 border rounded"
                    >
                        <b-row class="align-items-center">
                            <b-col md="1" class="text-center">
                                <i
                                    class="i-Arrow-Drag text-muted drag-handle"
                                    style="cursor: move; font-size: 20px"
                                ></i>
                            </b-col>
                            <b-col md="2">
                                <span
                                    class="badge p-2"
                                    :style="{
                                        backgroundColor: stage.color,
                                        color: '#fff',
                                        width: '100%',
                                    }"
                                >
                                    {{ stage.color }}
                                </span>
                            </b-col>
                            <b-col md="3">
                                <strong>{{ stage.name }}</strong>
                                <div v-if="stage.is_default_stage" class="mt-1">
                                    <span class="badge badge-success">{{
                                        $t("Default_Stage")
                                    }}</span>
                                </div>
                            </b-col>
                            <b-col md="4">
                                <small class="text-muted">{{
                                    stage.description || $t("No_description")
                                }}</small>
                            </b-col>
                            <b-col md="2" class="text-right">
                                <a
                                    v-if="can('crm_pipeline_stages_edit')"
                                    @click="Edit_Stage(stage)"
                                    v-b-tooltip.hover
                                    title="Edit"
                                    class="cursor-pointer mr-2"
                                >
                                    <i class="i-Edit text-25 text-success"></i>
                                </a>
                                <a
                                    v-if="can('crm_pipeline_stages_delete')"
                                    @click="Remove_Stage(stage.id)"
                                    v-b-tooltip.hover
                                    title="Delete"
                                    class="cursor-pointer"
                                >
                                    <i
                                        class="i-Close-Window text-25 text-danger"
                                    ></i>
                                </a>
                            </b-col>
                        </b-row>
                    </div>
                </draggable>
            </b-card>

            <b-card v-else>
                <div class="text-center py-5 text-muted">
                    <i class="i-File-Horizontal-Text text-32 mb-3"></i>
                    <p>{{ $t("No_stages_found") }}</p>
                    <b-button @click="New_Stage()" variant="primary" size="sm">
                        <i class="i-Add"></i>
                        {{ $t("Add_First_Stage") }}
                    </b-button>
                </div>
            </b-card>
        </div>

        <!-- Add/Edit Stage Modal -->
        <validation-observer ref="Stage_Form">
            <b-modal
                :id="'stageModal'"
                :title="isEditing ? $t('Edit_Stage') : $t('Add_Stage')"
                @ok="Save_Stage"
                @hidden="Reset_Modal"
                size="md"
            >
                <b-form>
                    <b-row>
                        <!-- Stage Name -->
                        <b-col md="12">
                            <validation-provider
                                name="Name"
                                :rules="{ required: true, min: 3 }"
                                v-slot="validationContext"
                            >
                                <b-form-group :label="$t('Name') + ' ' + '*'">
                                    <b-form-input
                                        v-model="form.name"
                                        :state="
                                            getValidationState(
                                                validationContext
                                            )
                                        "
                                        :placeholder="$t('Enter_Stage_Name')"
                                        required
                                    ></b-form-input>
                                    <b-form-invalid-feedback>{{
                                        validationContext.errors[0]
                                    }}</b-form-invalid-feedback>
                                </b-form-group>
                            </validation-provider>
                        </b-col>

                        <!-- Description -->
                        <b-col md="12">
                            <b-form-group :label="$t('Description')">
                                <b-form-textarea
                                    v-model="form.description"
                                    rows="3"
                                    :placeholder="$t('Enter_Stage_Description')"
                                ></b-form-textarea>
                            </b-form-group>
                        </b-col>

                        <!-- Color -->
                        <b-col md="6">
                            <b-form-group :label="$t('Color')">
                                <div class="d-flex align-items-center">
                                    <b-form-input
                                        v-model="form.color"
                                        type="color"
                                        class="mr-2"
                                        style="width: 60px; height: 38px"
                                    ></b-form-input>
                                    <b-form-input
                                        v-model="form.color"
                                        :placeholder="'#007bff'"
                                    ></b-form-input>
                                </div>
                            </b-form-group>
                        </b-col>

                        <!-- Sort Order -->
                        <b-col md="6">
                            <b-form-group :label="$t('Sort_Order')">
                                <b-form-input
                                    type="number"
                                    v-model="form.sort_order"
                                    min="0"
                                ></b-form-input>
                            </b-form-group>
                        </b-col>

                        <!-- Is Default Stage -->
                        <b-col md="12">
                            <div class="psx-form-check">
                                <input
                                    type="checkbox"
                                    v-model="form.is_default_stage"
                                    class="psx-checkbox psx-form-check-input"
                                    id="is_default_stage"
                                />
                                <label
                                    class="psx-form-check-label"
                                    for="is_default_stage"
                                >
                                    <h5>{{ $t("Set_as_Default_Stage") }}</h5>
                                </label>
                            </div>
                        </b-col>
                    </b-row>
                </b-form>
            </b-modal>
        </validation-observer>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import NProgress from "nprogress";
import draggable from "vuedraggable";
// Use window.axios which has baseURL configured in main.js

export default {
    name: "crm-pipeline-stages",
    components: {
        draggable,
    },
    metaInfo: {
        title: "Pipeline Stages",
    },
    data() {
        return {
            isLoading: true,
            pipelineId: null,
            pipeline: null,
            stages: [],
            isEditing: false,
            form: {
                id: null,
                name: "",
                description: "",
                color: "#007bff",
                sort_order: 0,
                is_default_stage: false,
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
        //---------------------------------------- Get Pipeline and Stages
        async Get_Pipeline_Data() {
            NProgress.start();
            try {
                const [pipelineRes, stagesRes] = await Promise.all([
                    window.axios.get(`crm/pipelines/${this.pipelineId}`),
                    window.axios.get(`crm/pipelines/${this.pipelineId}/stages`),
                ]);

                this.pipeline =
                    pipelineRes.data.pipeline || pipelineRes.data.data;
                this.stages =
                    stagesRes.data.stages || stagesRes.data.data || [];
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
                    this.$router.push({ name: "crm-pipelines" });
                }, 1500);
            }
        },
        //---------------------------------------- New Stage
        New_Stage() {
            this.isEditing = false;
            this.Reset_Modal();
            this.$bvModal.show("stageModal");
        },
        //---------------------------------------- Edit Stage
        Edit_Stage(stage) {
            this.isEditing = true;
            this.form = {
                id: stage.id,
                name: stage.name,
                description: stage.description || "",
                color: stage.color,
                sort_order: stage.sort_order || 0,
                is_default_stage: stage.is_default_stage || false,
            };
            this.$bvModal.show("stageModal");
        },
        //---------------------------------------- Reset Modal
        Reset_Modal() {
            this.form = {
                id: null,
                name: "",
                description: "",
                color: "#007bff",
                sort_order: 0,
                is_default_stage: false,
            };
        },
        //---------------------------------------- Save Stage
        Save_Stage(bvModalEvt) {
            bvModalEvt.preventDefault();
            this.$refs.Stage_Form.validate().then((success) => {
                if (!success) {
                    return;
                }

                const url = this.isEditing
                    ? `crm/pipeline-stages/${this.form.id}`
                    : "crm/pipeline-stages";
                const method = this.isEditing ? "put" : "post";
                const data = {
                    ...this.form,
                    pipeline_id: this.pipelineId,
                };

                NProgress.start();
                window.axios[method](url, data)
                    .then(() => {
                        this.makeToast(
                            "success",
                            this.$t("Successfully_Saved"),
                            this.$t("Success")
                        );
                        this.$bvModal.hide("stageModal");
                        this.Get_Pipeline_Data();
                    })
                    .catch((error) => {
                        this.makeToast(
                            "danger",
                            error.response?.data?.message ||
                                this.$t("Failed_to_save"),
                            this.$t("Error")
                        );
                    })
                    .finally(() => {
                        NProgress.done();
                    });
            });
        },
        //---------------------------------------- Remove Stage
        Remove_Stage(id) {
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
                    window.axios
                        .delete(`crm/pipeline-stages/${id}`)
                        .then(() => {
                            this.$swal(
                                this.$t("Delete_Deleted"),
                                this.$t("Deleted_in_successfully"),
                                "success"
                            );
                            this.Get_Pipeline_Data();
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
        //---------------------------------------- On Drag End
        onDragEnd() {
            const stageIds = this.stages.map((stage, index) => ({
                id: stage.id,
                sort_order: index + 1,
            }));

            NProgress.start();
            window.axios
                .post(`crm/pipeline-stages/reorder`, {
                    pipeline_id: this.pipelineId,
                    stages: stageIds,
                })
                .then(() => {
                    // Update sort orders locally
                    this.stages.forEach((stage, index) => {
                        stage.sort_order = index + 1;
                    });
                })
                .catch((error) => {
                    this.makeToast(
                        "danger",
                        this.$t("Failed_to_reorder"),
                        this.$t("Error")
                    );
                    // Reload to reset order
                    this.Get_Pipeline_Data();
                })
                .finally(() => {
                    NProgress.done();
                });
        },
        //------ Validation State
        getValidationState({ dirty, validated, valid = null }) {
            return dirty || validated ? valid : null;
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
        this.pipelineId = this.$route.params.id;
        if (this.pipelineId) {
            this.Get_Pipeline_Data();
        } else {
            this.$router.push({ name: "crm-pipelines" });
        }
    },
};
</script>

<style scoped>
.stage-item {
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.stage-item:hover {
    background-color: #e9ecef;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.drag-handle {
    cursor: move;
}
</style>
