<template>
  <AppPage>
    <PageHeader :title="t('admin.selectionGlobale.title')" :back-to="{ name: 'adminHome' }" />

    <section class="form-card app-card">
      <h3 class="section-title">
        {{ managingId ? t('admin.selectionGlobale.editTitle') : t('admin.selectionGlobale.addTitle') }}
      </h3>
      <form class="app-form" @submit.prevent="onSubmitName">
        <label class="app-field">
          <span>{{ t('admin.selectionGlobale.fields.name') }}</span>
          <InputText v-model="nameForm" :invalid="!!nameError" fluid />
          <small v-if="nameError" class="field-error">{{ nameError }}</small>
        </label>
        <Message v-if="formError" severity="error">{{ formError }}</Message>
        <div class="form-actions">
          <Button
            type="submit"
            :label="managingId ? t('admin.selectionGlobale.actions.save') : t('admin.selectionGlobale.actions.add')"
            :loading="submitting"
            class="w-full"
          />
          <Button
            v-if="managingId"
            type="button"
            severity="secondary"
            outlined
            :label="t('admin.selectionGlobale.actions.cancelEdit')"
            class="w-full"
            @click="resetManage"
          />
        </div>
      </form>
    </section>

    <section v-if="loading" class="state-card app-card">
      <p>{{ t('admin.selectionGlobale.loading') }}</p>
    </section>

    <section v-else-if="selections.length === 0" class="state-card app-card">
      <p>{{ t('admin.selectionGlobale.empty') }}</p>
    </section>

    <section v-else class="list">
      <article v-for="selection in selections" :key="selection.id" class="list-item app-card">
        <div class="list-item-body">
          <h4 class="item-title">{{ selection.name }}</h4>
        </div>
        <div class="list-item-actions">
          <Button
            type="button"
            icon="pi pi-cog"
            severity="secondary"
            outlined
            :aria-label="t('admin.selectionGlobale.actions.manage')"
            @click="startManage(selection.id)"
          />
          <Button
            type="button"
            icon="pi pi-trash"
            severity="danger"
            outlined
            :loading="deletingId === selection.id"
            :aria-label="t('admin.selectionGlobale.actions.delete')"
            @click="onDelete(selection.id)"
          />
        </div>
      </article>
    </section>

    <section v-if="detail" class="manage-card app-card">
      <h3 class="section-title">{{ t('admin.selectionGlobale.manageTitle', { name: detail.name }) }}</h3>

      <div class="manage-block">
        <h4>{{ t('admin.selectionGlobale.coachesTitle') }}</h4>
        <ul v-if="detail.coaches.length" class="chip-list">
          <li v-for="coach in detail.coaches" :key="coach.userId" class="chip-row">
            <span>{{ coach.email }}</span>
            <Button
              type="button"
              icon="pi pi-times"
              text
              rounded
              severity="danger"
              @click="removeCoach(coach.email)"
            />
          </li>
        </ul>
        <p v-else class="muted">{{ t('admin.selectionGlobale.noCoaches') }}</p>
        <form class="inline-form" @submit.prevent="addCoach">
          <InputText v-model="coachEmail" type="email" :placeholder="t('admin.selectionGlobale.coachEmail')" fluid />
          <Button type="submit" :label="t('admin.selectionGlobale.actions.addCoach')" :loading="coachSubmitting" />
        </form>
      </div>

      <div class="manage-block">
        <h4>{{ t('admin.selectionGlobale.playersTitle') }}</h4>
        <ul v-if="detail.players.length" class="chip-list">
          <li v-for="player in detail.players" :key="player.id" class="chip-row">
            <span>{{ player.firstName }} {{ player.lastName }}</span>
            <Button
              type="button"
              icon="pi pi-times"
              text
              rounded
              severity="danger"
              @click="removePlayer(player.id)"
            />
          </li>
        </ul>
        <p v-else class="muted">{{ t('admin.selectionGlobale.noPlayers') }}</p>
        <PlayerSearchSelect
          v-model="selectedPlayer"
          :label="t('admin.selectionGlobale.addPlayer')"
          :placeholder="t('admin.selectionGlobale.playerSearch')"
          :search-players="searchPlayers"
          @update:model-value="onPlayerPicked"
        />
      </div>
    </section>
  </AppPage>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import PlayerSearchSelect from '../components/players/PlayerSearchSelect.vue'
import type { Player } from '../models/Player'
import type { SelectionGlobaleDetail, SelectionGlobaleItem } from '../models/SelectionGlobale'
import { playersService } from '../services/players'
import { selectionGlobaleService } from '../services/selectionGlobale'

const { t } = useI18n()

const loading = ref(true)
const submitting = ref(false)
const coachSubmitting = ref(false)
const deletingId = ref<number | null>(null)
const managingId = ref<number | null>(null)
const formError = ref('')
const nameError = ref('')
const nameForm = ref('')
const selections = ref<SelectionGlobaleItem[]>([])
const detail = ref<SelectionGlobaleDetail | null>(null)
const coachEmail = ref('')
const selectedPlayer = ref<Player | null>(null)

async function loadList(): Promise<void> {
  loading.value = true
  try {
    selections.value = await selectionGlobaleService.listAdmin()
  } finally {
    loading.value = false
  }
}

function resetManage(): void {
  managingId.value = null
  nameForm.value = ''
  detail.value = null
  coachEmail.value = ''
  selectedPlayer.value = null
  formError.value = ''
  nameError.value = ''
}

async function startManage(id: number): Promise<void> {
  managingId.value = id
  detail.value = await selectionGlobaleService.getDetail(id)
  nameForm.value = detail.value.name
}

async function onSubmitName(): Promise<void> {
  nameError.value = nameForm.value.trim() === '' ? t('admin.selectionGlobale.validations.required') : ''
  if (nameError.value) {
    return
  }

  submitting.value = true
  formError.value = ''
  try {
    if (managingId.value) {
      await selectionGlobaleService.update(managingId.value, nameForm.value.trim())
      await startManage(managingId.value)
    } else {
      const created = await selectionGlobaleService.create(nameForm.value.trim())
      await loadList()
      await startManage(created.id)
    }
    await loadList()
  } catch {
    formError.value = t('admin.selectionGlobale.errors.saveFailed')
  } finally {
    submitting.value = false
  }
}

async function onDelete(id: number): Promise<void> {
  deletingId.value = id
  try {
    await selectionGlobaleService.delete(id)
    if (managingId.value === id) {
      resetManage()
    }
    await loadList()
  } finally {
    deletingId.value = null
  }
}

async function addCoach(): Promise<void> {
  if (!detail.value || coachEmail.value.trim() === '') {
    return
  }
  coachSubmitting.value = true
  try {
    detail.value = await selectionGlobaleService.addCoach(detail.value.id, coachEmail.value.trim())
    coachEmail.value = ''
  } finally {
    coachSubmitting.value = false
  }
}

async function removeCoach(email: string): Promise<void> {
  if (!detail.value) {
    return
  }
  detail.value = await selectionGlobaleService.removeCoach(detail.value.id, email)
}

function searchPlayers(query: string): Promise<Player[]> {
  return playersService.search(query)
}

async function onPlayerPicked(player: Player | null): Promise<void> {
  if (!detail.value || !player) {
    return
  }
  detail.value = await selectionGlobaleService.addPlayer(detail.value.id, player.id)
  selectedPlayer.value = null
}

async function removePlayer(playerId: number): Promise<void> {
  if (!detail.value) {
    return
  }
  detail.value = await selectionGlobaleService.removePlayer(detail.value.id, playerId)
}

onMounted(() => {
  void loadList()
})
</script>

<style scoped>
.form-card,
.manage-card,
.state-card {
  padding: var(--app-space-md);
  margin-bottom: var(--app-space-md);
}

.section-title {
  margin: 0 0 var(--app-space-md);
  font-size: 1rem;
}

.list {
  display: grid;
  gap: var(--app-space-sm);
}

.list-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--app-space-md);
  padding: var(--app-space-md);
}

.item-title {
  margin: 0;
  font-size: 1rem;
}

.list-item-actions {
  display: flex;
  gap: var(--app-space-xs);
}

.manage-block {
  display: grid;
  gap: var(--app-space-sm);
  margin-bottom: var(--app-space-lg);
}

.chip-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--app-space-xs);
}

.chip-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--app-space-sm);
  padding: var(--app-space-xs) var(--app-space-sm);
  border-radius: var(--app-radius-sm);
  background: var(--app-surface-muted, #f4f4f5);
}

.inline-form {
  display: grid;
  gap: var(--app-space-sm);
}

.muted {
  margin: 0;
  color: var(--app-text-muted);
  font-size: 0.875rem;
}

.form-actions {
  display: grid;
  gap: var(--app-space-sm);
}

.field-error {
  color: var(--app-danger);
  font-size: 0.8125rem;
}
</style>
