import { defineStore } from "pinia";
import { useCustomFetch } from "~/composables/useCustomFetch";

export const useSettingsStore = defineStore("settings", {
  state: () => ({
    settings: {} as Record<string, any>,
    loading: false,
    error: null as string | null,
  }),

  getters: {
    siteName: (state) => state.settings.site?.site_name || 'monogram E-Commerce',
    siteLogo: (state) => state.settings.site?.site_logo || null,
    siteAbout: (state) => state.settings.site?.site_about || 'Welcome to our e-commerce store. We offer quality products at affordable prices.',
    contactEmail: (state) => state.settings.contact?.contact_email || 'info@monogram.com',
    contactPhone: (state) => state.settings.contact?.contact_phone || '+63 123 456 7890',
    contactAddress: (state) => state.settings.contact?.contact_address || '123 Main Street, Manila, Philippines',
    contactFacebook: (state) => state.settings.contact?.contact_facebook || null,
    contactInstagram: (state) => state.settings.contact?.contact_instagram || null,
    contactYoutube: (state) => state.settings.contact?.contact_youtube || null,
  },

  actions: {
    async fetchSettings() {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>('/api/settings', { method: 'GET' });
        this.settings = response;
        return this.settings;
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to fetch settings';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchSettingsByGroup(group: string) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>(`/api/settings/group/${group}`, { method: 'GET' });
        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to fetch settings';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async getSetting(key: string) {
      try {
        const response = await useCustomFetch<any>(`/api/settings/${key}`, { method: 'GET' });
        return response.value;
      } catch (error: any) {
        console.error(`Failed to fetch setting ${key}:`, error);
        return null;
      }
    },

    async uploadLogo(file: File) {
      this.loading = true;
      this.error = null;
      try {
        const formData = new FormData();
        formData.append('logo', file);
        
        const response = await useCustomFetch<any>('/api/settings/upload-logo', {
          method: 'POST',
          body: formData,
        });
        
        // Update the settings with the new logo path
        if (this.settings.site) {
          this.settings.site.site_logo = response.path;
        }
        
        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to upload logo';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteLogo() {
      this.loading = true;
      this.error = null;
      try {
        await useCustomFetch('/api/settings/delete-logo', { method: 'DELETE' });
        
        // Remove logo from settings
        if (this.settings.site) {
          this.settings.site.site_logo = null;
        }
        
        return true;
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to delete logo';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async uploadTeamMemberImage(file: File) {
      this.loading = true;
      this.error = null;
      try {
        const formData = new FormData();
        formData.append('image', file);
        
        const response = await useCustomFetch<any>('/api/settings/upload-team-member-image', {
          method: 'POST',
          body: formData,
        });
        
        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to upload team member image';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteTeamMemberImage(imagePath: string) {
      this.loading = true;
      this.error = null;
      try {
        await useCustomFetch('/api/settings/delete-team-member-image', {
          method: 'DELETE',
          body: { image_path: imagePath }
        });
        
        return true;
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to delete team member image';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateSettings(data: { settings: Array<{ key: string; value: any; group: string; type: string }> }) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>('/api/settings', {
          method: 'POST',
          body: data,
        });
        
        // Update local settings
        this.settings = response;
        
        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to update settings';
        throw error;
      } finally {
        this.loading = false;
      }
    },

  },

  persist: true,
});