<template>
  <section class="danger-card app-card">
    <h3 class="section-title">{{ t('settings.delete.title') }}</h3>
    <p class="hint">{{ t('settings.delete.hint') }}</p>
    <Message v-if="deleteError" severity="error">{{ deleteError }}</Message>
    <Button
      type="button"
      :label="t('settings.delete.action')"
      severity="danger"
      outlined
      class="w-full"
      @click="deleteDialog = true"
    />
  </section>

  <Dialog
    v-model:visible="deleteDialog"
    :modal="true"
    :header="t('settings.delete.confirmTitle')"
    :closable="!deleting"
  >
    <p class="hint">{{ t('settings.delete.confirmMessage') }}</p>
    <div class="dialog-actions">
      <Button
        :label="t('settings.delete.cancel')"
        severity="secondary"
        :disabled="deleting"
        @click="deleteDialog = false"
      />
      <Button
        :label="t('settings.delete.confirm')"
        severity="danger"
        :loading="deleting"
        @click="onDeleteAccount"
      />
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Dialog from 'primevue/dialog'
import { accountService } from '../../services/account'
import { useAuthStore } from '../../stores/auth'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()

const deleteDialog = ref(false)
const deleting = ref(false)
const deleteError = ref('')

async function onDeleteAccount() {
  deleting.value = true
  deleteError.value = ''
  try {
    await accountService.deleteAccount()
    deleteDialog.value = false
    auth.logout()
    await router.push({ name: 'login' })
  } catch {
    deleteError.value = t('settings.errors.deleteFailed')
  } finally {
    deleting.value = false
  }
}
</script>

<style scoped>
.danger-card {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-md);
  margin-top: var(--app-space-md);
}

.dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--app-space-sm);
  margin-top: var(--app-space-md);
}

.section-title {
  margin: 0;
  font-size: 1rem;
}

.hint {
  margin: 0;
  font-size: 0.875rem;
  color: var(--app-text-muted);
  line-height: 1.45;
}

.w-full {
  width: 100%;
}
</style>
