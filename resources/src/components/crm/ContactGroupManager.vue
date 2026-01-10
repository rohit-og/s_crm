<template>
    <b-modal
        :id="'contactGroupModal'"
        :title="$t('Manage_Contact_Groups')"
        size="lg"
        @hidden="Reset_Modal"
        ref="groupModal"
    >
        <div v-if="contact">
            <b-form-group :label="$t('Contact')">
                <b-form-input :value="contact.name" disabled></b-form-input>
            </b-form-group>

            <b-form-group :label="$t('Select_Groups')">
                <div
                    v-for="group in allGroups"
                    :key="group.id"
                    class="mb-2 p-2 border rounded"
                >
                    <b-form-checkbox v-model="selectedGroups" :value="group.id">
                        <span
                            class="badge mr-2"
                            :style="{
                                backgroundColor: group.color,
                                color: '#fff',
                                padding: '4px 8px',
                            }"
                        >
                            {{ group.color }}
                        </span>
                        <strong>{{ group.name }}</strong>
                        <small
                            class="text-muted ml-2"
                            v-if="group.description"
                            >{{ group.description }}</small
                        >
                    </b-form-checkbox>
                </div>
                <div
                    v-if="allGroups.length === 0"
                    class="text-center text-muted py-3"
                >
                    {{ $t("No_groups_available") }}
                    <router-link :to="{ name: 'crm-groups' }" class="ml-2">
                        {{ $t("Create_Group") }}
                    </router-link>
                </div>
            </b-form-group>
        </div>

        <template #modal-footer>
            <b-button variant="secondary" @click="$refs.groupModal.hide()">
                {{ $t("Cancel") }}
            </b-button>
            <b-button variant="primary" @click="Save_Groups" :disabled="Saving">
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
    name: "crm-contact-group-manager",
    props: {
        contactId: {
            type: [String, Number],
            required: true,
        },
        currentGroups: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            contact: null,
            allGroups: [],
            selectedGroups: [],
            Saving: false,
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
                this.selectedGroups =
                    (this.contact.contact_groups || []).map((g) => g.id) ||
                    this.currentGroups.map((g) => g.id || g);
            } catch (error) {
                console.error("Error fetching contact:", error);
            }
        },
        //---------------------------------------- Fetch All Groups
        async Fetch_Groups() {
            try {
                const response = await axios.get("crm/contact-groups", {
                    params: { limit: -1 },
                });
                this.allGroups =
                    response.data.groups || response.data.data || [];
            } catch (error) {
                console.error("Error fetching groups:", error);
                this.allGroups = [];
            }
        },
        //---------------------------------------- Save Groups
        async Save_Groups() {
            this.Saving = true;
            NProgress.start();

            try {
                // Get current groups
                const currentGroupIds =
                    (this.contact?.contact_groups || []).map((g) => g.id) || [];

                // Find groups to add and remove
                const groupsToAdd = this.selectedGroups.filter(
                    (id) => !currentGroupIds.includes(id)
                );
                const groupsToRemove = currentGroupIds.filter(
                    (id) => !this.selectedGroups.includes(id)
                );

                // Add new groups
                if (groupsToAdd.length > 0) {
                    await axios.post(
                        `crm/contact-groups/${groupsToAdd[0]}/add-contacts`,
                        {
                            contact_ids: [this.contactId],
                        }
                    );
                    // If multiple groups, add to each
                    for (let i = 1; i < groupsToAdd.length; i++) {
                        await axios.post(
                            `crm/contact-groups/${groupsToAdd[i]}/add-contacts`,
                            {
                                contact_ids: [this.contactId],
                            }
                        );
                    }
                }

                // Remove groups
                if (groupsToRemove.length > 0) {
                    for (const groupId of groupsToRemove) {
                        await axios.post(
                            `crm/contact-groups/${groupId}/remove-contacts`,
                            {
                                contact_ids: [this.contactId],
                            }
                        );
                    }
                }

                this.$swal(
                    this.$t("Success"),
                    this.$t("Groups_updated_successfully"),
                    "success"
                );

                this.$refs.groupModal.hide();
                this.$emit("updated");
            } catch (error) {
                this.$swal(
                    this.$t("Error"),
                    error.response?.data?.message ||
                        this.$t("Failed_to_update_groups"),
                    "error"
                );
            } finally {
                this.Saving = false;
                NProgress.done();
            }
        },
        //---------------------------------------- Reset Modal
        Reset_Modal() {
            this.selectedGroups = [];
        },
    },
    async created() {
        await Promise.all([this.Get_Contact(), this.Fetch_Groups()]);
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
