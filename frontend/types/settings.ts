export interface TeamMember {
  name: string
  position: string
  email: string
  image: string | null
}

export interface SiteSettings {
  site_name: string
  site_logo: string | null
  site_about: string
}

export interface ContactSettings {
  contact_email: string
  contact_phone: string
  contact_address: string
  contact_facebook: string | null
  contact_instagram: string | null
  contact_youtube: string | null
}

export interface TeamSettings {
  team_members: TeamMember[]
}

export interface AllSettings {
  site: SiteSettings
  contact: ContactSettings
  team: TeamSettings
}

export interface Setting {
  id: number
  key: string
  value: string | null
  group: string
  type: 'text' | 'textarea' | 'image' | 'boolean' | 'json' | 'string'
  description?: string
  created_at: string
  updated_at: string
}

export interface SettingUpdatePayload {
  key: string
  value: any
  group: string
  type: string
}

export interface SettingsUpdatePayload {
  settings: SettingUpdatePayload[]
}
