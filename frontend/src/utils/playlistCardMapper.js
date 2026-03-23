function toNumber(value, fallback = 0) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
}

function mapPreviewSongs(songs) {
  if (!Array.isArray(songs)) {
    return [];
  }

  return songs
    .map((song) => {
      const artist = String(song?.artist || '').trim();
      const title = String(song?.title || '').trim();

      if (artist && title) return `${artist} - ${title}`;
      return title || artist;
    })
    .filter(Boolean)
    .slice(0, 3);
}

export function mapSearchPlaylistCard(item) {
  return {
    id: String(item?.id || ''),
    pantone_code: String(item?.pantone_code || ''),
    color_name: String(item?.color_name || ''),
    color_hex: String(item?.color_hex || '#b7aea6'),
    preview_songs: mapPreviewSongs(item?.preview_tracks),
    total_tracks: toNumber(item?.total_tracks)
  };
}

export function mapCategoryPlaylistCard(item) {
  return {
    id: String(item?.id || ''),
    pantone_code: String(item?.pantone_code || ''),
    color_name: String(item?.color_name || ''),
    color_hex: String(item?.color_hex || '#b7aea6'),
    preview_songs: mapPreviewSongs(item?.previewSongs),
    total_tracks: toNumber(item?.totalTracks)
  };
}
