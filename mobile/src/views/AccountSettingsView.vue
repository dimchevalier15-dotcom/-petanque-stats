<template>
  <AppPage>
    <PageHeader :title="t('settings.title')" />

    <section v-if="loading" class="state-card app-card">
      <p>{{ t('settings.loading') }}</p>
    </section>

    <template v-else>
      <section v-if="linkedPlayer" class="profile-card app-card">
        <h3 class="section-title">{{ t('settings.profile.title') }}</h3>
        <p class="hint">{{ t('settings.profile.hint') }}</p>

        <form class="app-form" @submit.prevent="onSaveProfile" novalidate>
          <label class="app-field">
            <span>{{ t('players.fields.firstName') }}</span>
            <InputText v-model="firstName" :invalid="!!profileErrors.firstName" autocomplete="given-name" fluid />
            <small v-if="profileErrors.firstName" class="field-error">{{ profileErrors.firstName }}</small>
          </label>

          <label class="app-field">
            <span>{{ t('players.fields.lastName') }}</span>
            <InputText v-model="lastName" :invalid="!!profileErrors.lastName" autocomplete="family-name" fluid />
            <small v-if="profileErrors.lastName" class="field-error">{{ profileErrors.lastName }}</small>
          </label>

          <label class="app-field">
            <span>{{ t('players.fields.nickname') }}</span>
            <InputText v-model="nickname" autocomplete="nickname" fluid />
          </label>

          <Message v-if="profileErrorMessage" severity="error">{{ profileErrorMessage }}</Message>
          <Message v-if="profileSaved" severity="success">{{ t('settings.profile.saved') }}</Message>

          <Button
            type="submit"
            :label="t('settings.profile.save')"
            :disabled="!canSaveProfile || savingProfile"
            :loading="savingProfile"
            class="w-full"
          />
        </form>
      </section>

      <section v-else class="link-card app-card">
        <h3 class="section-title">{{ t('settings.unlinked.title') }}</h3>
        <p class="hint">{{ t('settings.unlinked.hint') }}</p>

        <PlayerSearchSelect
          v-model="selectedPlayer"
          :label="t('settings.unlinked.searchLabel')"
          :placeholder="t('settings.unlinked.searchPlaceholder')"
          :empty-hint="t('settings.unlinked.empty')"
          unlinked-only
          authenticated-search
        />

        <Message v-if="errorMessage" severity="error">{{ errorMessage }}</Message>

        <Button
          type="button"
          :label="t('settings.unlinked.linkAction')"
          :disabled="!selectedPlayer || linking"
          :loading="linking"
          class="w-full"
          @click="onLink"
        />
      </section>
    </template>

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
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Dialog from 'primevue/dialog'
import { useRouter } from 'vue-router'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import PlayerSearchSelect from '../components/players/PlayerSearchSelect.vue'
import { accountService } from '../services/account'
import { useAuthStore } from '../stores/auth'
import type { Player } from '../models/Player'
import axios from 'axios'

type ProfileErrors = { firstName?: string; lastName?: string }

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()

const loading = ref(true)
const linking = ref(false)
const savingProfile = ref(false)
const profileSaved = ref(false)
const linkedPlayer = ref<Player | null>(null)
const selectedPlayer = ref<Player | null>(null)
const errorKey = ref<string | null>(null)
const profileErrorKey = ref<string | null>(null)

const firstName = ref('')
const lastName = ref('')
const nickname = ref('')
const profileErrors = reactive<ProfileErrors>({})
const deleteDialog = ref(false)
const deleting = ref(false)
const deleteError = ref('')

const errorMessage = computed(() => (errorKey.value ? t(errorKey.value) : ''))
const profileErrorMessage = computed(() => (profileErrorKey.value ? t(profileErrorKey.value) : ''))

const canSaveProfile = computed(() => firstName.value.trim() !== '' && lastName.value.trim() !== '')

watch([firstName, lastName, nickname], () => {
  profileSaved.value = false
})

function syncProfileForm(player: Player): void {
  firstName.value = player.firstName
  lastName.value = player.lastName
  nickname.value = player.nickname
}

function updateAuthUser(player: Player): void {
  if (!auth.user) {
    return
  }
  auth.user = {
    ...auth.user,
    playerId: player.id,
    firstName: player.firstName,
    lastName: player.lastName,
    nickname: player.nickname,
  }
}

async function loadLinkedPlayer() {
  loading.value = true
  errorKey.value = null
  try {
    linkedPlayer.value = await accountService.getLinkedPlayer()
    if (linkedPlayer.value) {
      syncProfileForm(linkedPlayer.value)
    }
  } catch {
    errorKey.value = 'settings.errors.loadFailed'
  } finally {
    loading.value = false
  }
}

function validateProfileLocal(): boolean {
  profileErrors.firstName = firstName.value.trim() === '' ? t('players.validations.required') : undefined
  profileErrors.lastName = lastName.value.trim() === '' ? t('players.validations.required') : undefined
  return !profileErrors.firstName && !profileErrors.lastName
}

async function onSaveProfile() {
  if (!validateProfileLocal()) {
    return
  }

  savingProfile.value = true
  profileErrorKey.value = null
  profileSaved.value = false

  try {
    const payload = {
      firstName: firstName.value.trim(),
      lastName: lastName.value.trim(),
      nickname: nickname.value.trim() || undefined,
    }
    linkedPlayer.value = await accountService.updateProfile(payload)
    syncProfileForm(linkedPlayer.value)
    updateAuthUser(linkedPlayer.value)
    profileSaved.value = true
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const serverErrors = error.response?.data?.errors as Record<string, string> | undefined
      if (serverErrors) {
        profileErrors.firstName = serverErrors.firstName
        profileErrors.lastName = serverErrors.lastName
        return
      }
      const code = error.response?.data?.error as string | undefined
      if (code === 'no_linked_player') {
        profileErrorKey.value = 'settings.errors.noLinkedPlayer'
      } else if (code === 'player_not_found') {
        profileErrorKey.value = 'settings.errors.playerNotFound'
      } else {
        profileErrorKey.value = 'settings.errors.profileSaveFailed'
      }
    } else {
      profileErrorKey.value = 'settings.errors.profileSaveFailed'
    }
  } finally {
    savingProfile.value = false
  }
}

async function onLink() {
  if (!selectedPlayer.value) {
    return
  }

  linking.value = true
  errorKey.value = null
  try {
    linkedPlayer.value = await accountService.linkPlayer(selectedPlayer.value.id)
    selectedPlayer.value = null
    syncProfileForm(linkedPlayer.value)
    updateAuthUser(linkedPlayer.value)
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const code = error.response?.data?.error as string | undefined
      if (code === 'player_already_linked') {
        errorKey.value = 'settings.errors.playerAlreadyLinked'
      } else if (code === 'player_not_found') {
        errorKey.value = 'settings.errors.playerNotFound'
      } else if (code === 'user_already_has_player') {
        errorKey.value = 'settings.errors.userAlreadyHasPlayer'
      } else {
        errorKey.value = 'settings.errors.linkFailed'
      }
    } else {
      errorKey.value = 'settings.errors.linkFailed'
    }
  } finally {
    linking.value = false
  }
}

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

onMounted(loadLinkedPlayer)
</script>

<style scoped>
.state-card,
.profile-card,
.link-card,
.danger-card {
  padding: var(--app-space-lg);
  display: grid;
  gap: var(--app-space-md);
}

.danger-card {
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

.field-error {
  color: #c24141;
  font-size: 0.8125rem;
}

.w-full {
  width: 100%;
}
</style>
