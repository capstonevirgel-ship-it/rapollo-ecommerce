import { defineStore } from 'pinia'
import type { AllSettings, Setting, SettingsUpdatePayload } from '~/types/settings'

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    settings: null as AllSettings | null,
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchSettings() {
      this.loading = true
      this.error = null
      try {
        const response = await useCustomFetch<AllSettings>('/api/settings', {
          method: 'GET',
        })
        this.settings = response
        return response
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to fetch settings'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchSettingsByGroup(group: string) {
      this.loading = true
      this.error = null
      try {
        const response = await useCustomFetch<any>(`/api/settings/group/${group}`, {
          method: 'GET',
        })
        return response
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to fetch settings'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchSettingByKey(key: string) {
      this.loading = true
      this.error = null
      try {
        const response = await useCustomFetch<{ key: string; value: any }>(`/api/settings/${key}`, {
          method: 'GET',
        })
        return response.value
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to fetch setting'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateSettings(payload: SettingsUpdatePayload) {
      this.loading = true
      this.error = null
      try {
        const response = await useCustomFetch<{ message: string; settings: AllSettings }>('/api/settings', {
          method: 'POST',
          body: payload,
        })
        this.settings = response.settings
        return response
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to update settings'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateSingleSetting(key: string, value: any, group?: string, type?: string) {
      this.loading = true
      this.error = null
      try {
        const response = await useCustomFetch<{ message: string; setting: { key: string; value: any } }>(
          `/api/settings/${key}`,
          {
            method: 'PUT',
            body: { value, group, type },
          }
        )
        // Update local state
        await this.fetchSettings()
        return response
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to update setting'
        throw error
      } finally {
        this.loading = false
      }
    },

    async uploadLogo(file: File) {
      this.loading = true
      this.error = null
      try {
        const formData = new FormData()
        formData.append('logo', file)

        const response = await useCustomFetch<{ message: string; path: string; url: string }>(
          '/api/settings/upload-logo',
          {
            method: 'POST',
            body: formData,
          }
        )

        // Refresh settings
        await this.fetchSettings()
        return response
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to upload logo'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteLogo() {
      this.loading = true
      this.error = null
      try {
        const response = await useCustomFetch<{ message: string }>('/api/settings/delete-logo', {
          method: 'DELETE',
        })

        // Refresh settings
        await this.fetchSettings()
        return response
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to delete logo'
        throw error
      } finally {
        this.loading = false
      }
    },

    async uploadTeamMemberImage(file: File) {
      this.loading = true
      this.error = null
      try {
        const formData = new FormData()
        formData.append('image', file)

        const response = await useCustomFetch<{ message: string; path: string; url: string }>(
          '/api/settings/upload-team-member-image',
          {
            method: 'POST',
            body: formData,
          }
        )

        return response
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to upload team member image'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteTeamMemberImage(path: string) {
      this.loading = true
      this.error = null
      try {
        const response = await useCustomFetch<{ message: string }>(
          '/api/settings/delete-team-member-image',
          {
            method: 'DELETE',
            body: { path },
          }
        )

        return response
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to delete team member image'
        throw error
      } finally {
        this.loading = false
      }
    },

    async toggleMaintenance(enabled: boolean, message?: string) {
      this.loading = true
      this.error = null
      try {
        const response = await useCustomFetch<{ message: string; maintenance_mode: boolean }>(
          '/api/settings/toggle-maintenance',
          {
            method: 'POST',
            body: { enabled, message },
          }
        )

        // Refresh settings
        await this.fetchSettings()
        return response
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to toggle maintenance mode'
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
