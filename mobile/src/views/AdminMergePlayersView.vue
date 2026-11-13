<template>
  <AppPage>
    <PageHeader :title="t('admin.merge.title')" :back-to="{ name: 'adminHome' }" />

    <section class="merge-card app-card">
      <p class="hint">{{ t('admin.merge.hint') }}</p>

      <PlayerSearchSelect
        v-model="keepPlayer"
        :label="t('admin.merge.keepLabel')"
        :placeholder="t('admin.merge.searchPlaceholder')"
        :empty-hint="t('admin.merge.empty')"
      />
      <p v-if="preview" class="account-line">{{ accountLine(preview.keep) }}</p>

      <PlayerSearchSelect
        v-model="dropPlayer"
        :label="t('admin.merge.dropLabel')"
        :placeholder="t('admin.merge.searchPlaceholder')"
        :empty-hint="t('admin.merge.empty')"
      />
      <p v-if="preview" class="account-line">{{ accountLine(preview.drop) }}</p>

      <p v-if="preview && !preview.requiresUserChoice && singleEmail" class="account-line">
        {{ t('admin.merge.singleAccount', { email: singleEmail }) }}
      </p>

      <fieldset v-if="preview?.requiresUserChoice" class="user-choice">
        <legend>{{ t('admin.merge.chooseUser') }}</legend>
        <label v-for="option in userOptions" :key="option.userId" class="choice">
          <input v-model="keepUserId" type="radio" name="keep-user" :value="option.userId" />
          <span>{{ option.email }}</span>
        </label>
      </fieldset>

      <Message v-if="errorMessage" severity="error">{{ errorMessage }}</Message>
      <Message v-if="successMessage" severity="success">{{ successMessage }}</Message>

      <Button
        type="button"
        class="w-full"
        :label="t('admin.merge.confirm')"
        :loading="submitting"
        :disabled="!canSubmit"
        @click="onMerge"
      />
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import axios from 'axios'
import Button from 'primevue/button'
import Message from 'primevue/message'
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import PlayerSearchSelect from '../components/players/PlayerSearchSelect.vue'
import { formatPlayerLabel } from '../composables/usePlayerSearch'
import type { Player } from '../models/Player'
import {
  adminPlayersService,
  type PlayerMergePreview,
  type PlayerMergeSide,
} from '../services/adminPlayers'

const { t } = useI18n()
const keepPlayer = ref<Player | null>(null)
const dropPlayer = ref<Player | null>(null)
const preview = ref<PlayerMergePreview | null>(null)
const keepUserId = ref<number | null>(null)
const submitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
let previewRequest = 0

const userOptions = computed(() => {
  if (!preview.value?.requiresUserChoice) {
    return []
  }
  return [preview.value.keep, preview.value.drop]
    .filter((side): side is PlayerMergeSide & { userId: number; userEmail: string } =>
      side.userId !== null && side.userEmail !== null,
    )
    .map((side) => ({ userId: side.userId, email: side.userEmail }))
})

const singleEmail = computed(() => {
  if (!preview.value || preview.value.requiresUserChoice) {
    return null
  }
  return preview.value.keep.userEmail ?? preview.value.drop.userEmail
})

const canSubmit = computed(() => {
  if (!keepPlayer.value || !dropPlayer.value || keepPlayer.value.id === dropPlayer.value.id) {
    return false
  }
  if (preview.value?.requiresUserChoice && keepUserId.value === null) {
    return false
  }
  return preview.value !== null && !submitting.value
})

watch([keepPlayer, dropPlayer], async ([keep, drop]) => {
  const requestId = ++previewRequest
  errorMessage.value = ''
  preview.value = null
  keepUserId.value = null
  if (keep || drop) {
    successMessage.value = ''
  }
  if (!keep || !drop) {
    return
  }
  if (keep.id === drop.id) {
    errorMessage.value = t('admin.merge.errors.samePlayer')
    return
  }
  try {
    const next = await adminPlayersService.mergePreview(keep.id, drop.id)
    if (requestId !== previewRequest) {
      return
    }
    preview.value = next
  } catch (error) {
    if (requestId !== previewRequest) {
      return
    }
    errorMessage.value = messageFromError(error, 'previewFailed')
  }
})

function accountLine(side: PlayerMergeSide): string {
  if (side.userEmail) {
    return t('admin.merge.linkedAccount', { email: side.userEmail })
  }
  return t('admin.merge.noAccount')
}

async function onMerge(): Promise<void> {
  if (!keepPlayer.value || !dropPlayer.value || !preview.value) {
    return
  }
  const keepLabel = formatPlayerLabel(keepPlayer.value)
  const dropLabel = formatPlayerLabel(dropPlayer.value)
  if (!window.confirm(t('admin.merge.confirmMessage', { keep: keepLabel, drop: dropLabel }))) {
    return
  }

  submitting.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await adminPlayersService.merge(
      keepPlayer.value.id,
      dropPlayer.value.id,
      preview.value.requiresUserChoice ? keepUserId.value : null,
    )
    successMessage.value = t('admin.merge.success', { keep: keepLabel, drop: dropLabel })
    keepPlayer.value = null
    dropPlayer.value = null
    preview.value = null
    keepUserId.value = null
  } catch (error) {
    errorMessage.value = messageFromError(error, 'failed')
  } finally {
    submitting.value = false
  }
}

function messageFromError(error: unknown, fallback: 'previewFailed' | 'failed'): string {
  if (axios.isAxiosError(error)) {
    const code = error.response?.data?.code
    if (typeof code === 'string' && code !== 'invalid') {
      const key = `admin.merge.errors.${code}`
      const translated = t(key)
      if (translated !== key) {
        return translated
      }
    }
  }
  return t(`admin.merge.errors.${fallback}`)
}
</script>

<style scoped>
.merge-card {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-md);
}

.hint,
.account-line {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.45;
}

.hint,
.account-line {
  color: var(--app-text-muted);
}

.user-choice {
  margin: 0;
  padding: var(--app-space-sm);
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius);
  display: grid;
  gap: var(--app-space-sm);
}

.user-choice legend {
  padding: 0 0.25rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.choice {
  display: flex;
  align-items: center;
  gap: var(--app-space-sm);
  font-size: 0.875rem;
}

.w-full {
  width: 100%;
}
</style>
