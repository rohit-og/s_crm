<template>
    <b-modal
        :id="'contactTagModal'"
        :title="$t('Manage_Contact_Tags')"
        size="lg"
        @hidden="Reset_Modal"
        ref="tagModal"
    >
        <div v-if="contact">
            <b-form-group :label="$t('Contact')">
                <b-form-input :value="contact.name" disabled></b-form-input>
            </b-form-group>

            <b-form-group :label="$t('Select_Tags')">
                <div class="mb-3">
                    <div
                        v-for="tag in allTags"
                        :key="tag.id"
                        class="mb-2 p-2 border rounded"
                    >
                        <b-form-checkbox v-model="selectedTags" :value="tag.id">
                            <span
                                class="badge mr-2"
                                :style="{
                                    backgroundColor: tag.color,
                                    color: '#fff',
                                    padding: '4px 8px',
                                }"
                            >
                                {{ tag.color }}
                            </span>
                            <strong>{{ tag.name }}</strong>
                        </b-form-checkbox>
                    </div>
                    <div
                        v-if="allTags.length === 0"
                        class="text-center text-muted py-3"
                    >
                        {{ $t("No_tags_available") }}
                        <router-link :to="{ name: 'crm-tags' }" class="ml-2">
                            {{ $t("Create_Tag") }}
                        </router-link>
                    </div>
                </div>

                <!-- Quick Create Tag -->
                <b-card class="mt-3">
                    <h6 class="mb-3">{{ $t("Quick_Create_Tag") }}</h6>
                    <b-row>
                        <b-col md="6">
                            <b-form-group :label="$t('Tag_Name')">
                                <b-form-input
                                    v-model="newTag.name"
                                    :placeholder="$t('Enter_Tag_Name')"
                                ></b-form-input>
                            </b-form-group>
                        </b-col>
                        <b-col md="4">
                            <b-form-group :label="$t('Color')">
                                <b-form-input
                                    v-model="newTag.color"
                                    type="color"
                                ></b-form-input>
                            </b-form-group>
                        </b-col>
                        <b-col md="2" class="d-flex align-items-end">
                            <b-button
                                variant="primary"
                                size="sm"
                                block
                                @click="Create_Tag"
                                :disabled="!newTag.name"
                            >
                                <i class="i-Add"></i>
                            </b-button>
                        </b-col>
                    </b-row>
                </b-card>
            </b-form-group>
        </div>

        <template #modal-footer>
            <b-button variant="secondary" @click="$refs.tagModal.hide()">
                {{ $t("Cancel") }}
            </b-button>
            <b-button variant="primary" @click="Save_Tags" :disabled="Saving">
                <span v-if="Saving" class="spinner spinner-sm mr-2"></span>
                {{ $t("Save") }}
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import NProgress from "nprogress";
import axios from "axios";

export default {
    name: "crm-contact-tag-manager",
    props: {
        contactId: {
            type: [String, Number],
            required: true,
        },
        currentTags: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            contact: null,
            allTags: [],
            selectedTags: [],
            Saving: false,
            newTag: {
                name: "",
                color: "#007bff",
            },
        };
    },
    methods: {
        //---------------------------------------- Get Contact
        async Get_Contact() {
            try {
                const response = await axios.get(
                    `crm/contacts/${this.contactId}`
                );
                this.contact = response.data.contact || response.data.data;
                this.selectedTags =
                    (this.contact.tags || []).map((t) => t.id) ||
                    this.currentTags.map((t) => t.id || t);
            } catch (error) {
                console.error("Error fetching contact:", error);
            }
        },
        //---------------------------------------- Fetch All Tags
        async Fetch_Tags() {
            try {
                const response = await axios.get("crm/tags", {
                    params: { limit: -1 },
                });
                this.allTags = response.data.tags || response.data.data || [];
            } catch (error) {
                console.error("Error fetching tags:", error);
                this.allTags = [];
            }
        },
        //---------------------------------------- Create Tag
        async Create_Tag() {
            if (!this.newTag.name) {
                this.makeToast(
                    "warning",
                    this.$t("Tag_name_is_required"),
                    this.$t("Validation_Error")
                );
                return;
            }

            NProgress.start();
            try {
                const response = await axios.post("crm/tags", this.newTag);
                const newTag = response.data.tag || response.data.data;
                this.allTags.push(newTag);
                this.selectedTags.push(newTag.id);
                this.newTag = { name: "", color: "#007bff" };
                this.makeToast(
                    "success",
                    this.$t("Tag_created_successfully"),
                    this.$t("Success")
                );
            } catch (error) {
                this.$swal(
                    this.$t("Error"),
                    error.response?.data?.message ||
                        this.$t("Failed_to_create_tag"),
                    "error"
                );
            } finally {
                NProgress.done();
            }
        },
        //---------------------------------------- Save Tags
        async Save_Tags() {
            this.Saving = true;
            NProgress.start();

            try {
                // Get current tags
                const currentTagIds =
                    (this.contact?.tags || []).map((t) => t.id) || [];

                // Find tags to add and remove
                const tagsToAdd = this.selectedTags.filter(
                    (id) => !currentTagIds.includes(id)
                );
                const tagsToRemove = currentTagIds.filter(
                    (id) => !this.selectedTags.includes(id)
                );

                // Add new tags
                if (tagsToAdd.length > 0) {
                    await axios.post(
                        `crm/tags/${tagsToAdd[0]}/attach-contact`,
                        {
                            contact_id: this.contactId,
                        }
                    );
                    // If multiple tags, attach to each
                    for (let i = 1; i < tagsToAdd.length; i++) {
                        await axios.post(
                            `crm/tags/${tagsToAdd[i]}/attach-contact`,
                            {
                                contact_id: this.contactId,
                            }
                        );
                    }
                }

                // Remove tags
                if (tagsToRemove.length > 0) {
                    for (const tagId of tagsToRemove) {
                        await axios.post(`crm/tags/${tagId}/detach-contact`, {
                            contact_id: this.contactId,
                        });
                    }
                }

                this.$swal(
                    this.$t("Success"),
                    this.$t("Tags_updated_successfully"),
                    "success"
                );

                this.$refs.tagModal.hide();
                this.$emit("updated");
            } catch (error) {
                this.$swal(
                    this.$t("Error"),
                    error.response?.data?.message ||
                        this.$t("Failed_to_update_tags"),
                    "error"
                );
            } finally {
                this.Saving = false;
                NProgress.done();
            }
        },
        //---------------------------------------- Reset Modal
        Reset_Modal() {
            this.selectedTags = [];
            this.newTag = { name: "", color: "#007bff" };
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
        await Promise.all([this.Get_Contact(), this.Fetch_Tags()]);
    },
    watch: {
        contactId() {
            if (this.contactId) {
                this.Get_Contact();
            }
        },
    },
};
</script>

<style scoped>
.border {
    border-color: #dee2e6 !important;
}
</style>
