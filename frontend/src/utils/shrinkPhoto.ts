/** Longest edge we upload; the server crops to a 256px square from this. */
const MAX_EDGE = 768

/**
 * Phone photos are several MB; shrink them on the device before uploading.
 * createImageBitmap applies EXIF rotation, so sideways photos come out upright.
 */
export async function shrinkPhoto(file: File): Promise<Blob> {
  let bitmap: ImageBitmap
  try {
    bitmap = await createImageBitmap(file)
  } catch {
    throw new Error('That file isn’t a photo this device can open.')
  }

  const scale = Math.min(1, MAX_EDGE / Math.max(bitmap.width, bitmap.height))
  const canvas = document.createElement('canvas')
  canvas.width = Math.round(bitmap.width * scale)
  canvas.height = Math.round(bitmap.height * scale)
  canvas.getContext('2d')?.drawImage(bitmap, 0, 0, canvas.width, canvas.height)
  bitmap.close()

  return new Promise((resolve, reject) => {
    canvas.toBlob(
      (blob) => (blob ? resolve(blob) : reject(new Error('Couldn’t read that photo.'))),
      'image/jpeg',
      0.9,
    )
  })
}
