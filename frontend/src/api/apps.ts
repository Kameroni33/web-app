import { http } from './http'

/** An app the signed-in user may open (backend config/apps.php). */
export interface AvailableApp {
  key: string
  name: string
  description: string
}

export async function fetchAvailableApps(): Promise<AvailableApp[]> {
  const { data } = await http.get<{ data: AvailableApp[] }>('/api/apps')
  return data.data
}
