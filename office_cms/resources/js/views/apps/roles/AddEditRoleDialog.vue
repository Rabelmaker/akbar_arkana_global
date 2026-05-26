<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  role: {
    type: Object,
    default: () => null,
  },
  permissions: {
    type: Array,
    default: () => [],
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'submit',
])

const form = reactive({
  name: '',
  permissions: [],
})

watch(
  () => [props.isDialogVisible, props.role, props.permissions],
  () => {
    if (!props.isDialogVisible)
      return

    form.name = props.role?.name ?? ''
    form.permissions = [...(props.role?.permissions ?? [])]
  },
  { immediate: true },
)

const close = () => {
  emit('update:isDialogVisible', false)
}

const submit = () => {
  emit('submit', {
    id: props.role?.id ?? null,
    name: form.name,
    permissions: form.permissions,
  })
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 900"
    :model-value="props.isDialogVisible"
    @update:model-value="close"
  >
    <VCard class="pa-sm-11 pa-3">
      <DialogCloseBtn
        variant="text"
        size="default"
        @click="close"
      />

      <VCardText>
        <div class="text-center mb-10">
          <h4 class="text-h4 mb-2">
            {{ props.role ? 'Edit' : 'Add' }} Role
          </h4>

          <p class="text-body-1">
            {{ props.role ? 'Update role name and manage granted permissions.' : 'Create a role and assign its permissions.' }}
          </p>
        </div>

        <VForm @submit.prevent="submit">
          <VTextField
            v-model="form.name"
            label="Role Name"
            placeholder="Enter Role Name"
            class="mb-6"
            :error="Boolean(props.errors.name?.[0])"
            :error-messages="props.errors.name?.[0]"
          />

          <VAlert
            v-if="props.errors.permissions?.[0]"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            {{ props.errors.permissions[0] }}
          </VAlert>

          <h5 class="text-h5 mb-4">
            Role Permissions
          </h5>

          <VRow>
            <VCol
              v-for="permission in props.permissions"
              :key="permission"
              cols="12"
              sm="6"
              md="4"
            >
              <VCheckbox
                v-model="form.permissions"
                :label="permission"
                :value="permission"
                density="comfortable"
              />
            </VCol>
          </VRow>

          <div class="d-flex align-center justify-center gap-3 mt-6">
            <VBtn
              type="submit"
              :loading="props.loading"
              :disabled="props.loading"
            >
              Submit
            </VBtn>

            <VBtn
              color="secondary"
              variant="outlined"
              :disabled="props.loading"
              @click="close"
            >
              Cancel
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
