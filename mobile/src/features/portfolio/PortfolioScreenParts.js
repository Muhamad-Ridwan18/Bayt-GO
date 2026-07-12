import React from 'react';
import { Image, ScrollView, StyleSheet, Text, TextInput, View, Dimensions } from 'react-native';
import { Camera, ChevronDown, ChevronUp, CirclePlus, CloudUpload, Expand, Images, Pencil, Trash2 } from 'lucide-react-native';
import AuthenticatedImage from '../../components/AuthenticatedImage';
import { Button, Card, PressableScale, UploadPreviewStrip } from '../../ui';
import { colors, radius, spacing, typography } from '../../theme/tokens';

const { width: SCREEN_W } = Dimensions.get('window');
export const CARD_W = (SCREEN_W - 40 - 12) / 2;

export function StatCard({ label, value, Icon }) {
  return (
    <Card style={styles.statCard} padding={spacing.lg} elevated={false}>
      <View style={styles.statIcon}>
        <Icon size={16} color={colors.baytgo} strokeWidth={2} />
      </View>
      <Text style={styles.statValue}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </Card>
  );
}

export function AlbumCard({ item, token, onPreview, onEdit, onDelete, deleting }) {
  return (
    <Card style={styles.albumCard} padding={0} elevated={false}>
      <PressableScale onPress={onPreview} haptic="light">
        <View style={styles.albumCoverWrap}>
          {item.cover_url ? (
            <AuthenticatedImage uri={item.cover_url} token={token} style={styles.albumCover} />
          ) : (
            <View style={[styles.albumCover, styles.albumCoverPlaceholder]}>
              <Images size={28} color={colors.textMuted} strokeWidth={1.8} />
            </View>
          )}
          <View style={styles.albumOverlay}>
            <Expand size={16} color={colors.white} strokeWidth={2} />
          </View>
          <View style={styles.photoCount}>
            <Camera size={11} color={colors.white} strokeWidth={2} />
            <Text style={styles.photoCountText}>{item.images_count || 0}</Text>
          </View>
        </View>
      </PressableScale>

      <View style={styles.albumMeta}>
        <Text style={styles.albumTitle} numberOfLines={2}>{item.title}</Text>
        <View style={styles.albumActions}>
          <PressableScale onPress={onEdit} haptic="light" style={styles.albumActionBtn}>
            <Pencil size={16} color={colors.baytgo} strokeWidth={2} />
          </PressableScale>
          <PressableScale
            onPress={onDelete}
            disabled={deleting}
            haptic="light"
            style={[styles.albumActionBtn, styles.albumDeleteBtn]}
          >
            <Trash2 size={16} color={colors.error} strokeWidth={2} />
          </PressableScale>
        </View>
      </View>
    </Card>
  );
}

const styles = StyleSheet.create({
  statCard: { flex: 1, alignItems: 'center' },
  statIcon: {
    width: 32,
    height: 32,
    borderRadius: radius.sm - 2,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
  },
  statValue: { ...typography.title, fontSize: 22, color: colors.baytgo },
  statLabel: { marginTop: 2, ...typography.small, color: colors.textSecondary },
  albumCard: { width: CARD_W, overflow: 'hidden' },
  albumCoverWrap: { position: 'relative' },
  albumCover: { width: '100%', height: CARD_W * 0.85 },
  albumCoverPlaceholder: {
    backgroundColor: colors.surface,
    alignItems: 'center',
    justifyContent: 'center',
  },
  albumOverlay: {
    position: 'absolute',
    right: spacing.sm,
    bottom: spacing.sm,
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: 'rgba(15,46,40,0.55)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  photoCount: {
    position: 'absolute',
    left: spacing.sm,
    top: spacing.sm,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: 'rgba(15,46,40,0.65)',
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
    borderRadius: radius.full,
  },
  photoCountText: { ...typography.small, fontSize: 11, color: colors.white },
  albumMeta: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.sm, padding: spacing.md },
  albumTitle: { flex: 1, ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.textPrimary, lineHeight: 17 },
  albumActions: { gap: 6 },
  albumActionBtn: {
    width: 30,
    height: 30,
    borderRadius: radius.sm - 4,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  albumDeleteBtn: { backgroundColor: colors.errorLight },
});

export function PortfolioCreateSection({
  formOpen, onToggleForm, title, description, images, submitting,
  onChangeTitle, onChangeDescription, onPickImages, onRemoveImage, onSubmit,
}) {
  return (
    <>
      <PressableScale onPress={onToggleForm} haptic="light">
        <Card style={formStyles.formToggle} padding={spacing.lg} elevated={false}>
          <View style={formStyles.formToggleLeft}>
            <View style={formStyles.formToggleIcon}>
              <CirclePlus size={18} color={colors.baytgo} strokeWidth={2} />
            </View>
            <View>
              <Text style={formStyles.formToggleTitle}>Tambah album baru</Text>
              <Text style={formStyles.formToggleSub}>Judul kegiatan + beberapa foto sekaligus</Text>
            </View>
          </View>
          {formOpen ? (
            <ChevronUp size={20} color={colors.textSecondary} strokeWidth={2} />
          ) : (
            <ChevronDown size={20} color={colors.textSecondary} strokeWidth={2} />
          )}
        </Card>
      </PressableScale>

      {formOpen ? (
        <Card style={formStyles.formCard} padding={spacing.lg} elevated={false}>
          <Text style={formStyles.fieldLabel}>Judul kegiatan</Text>
          <TextInput
            style={formStyles.input}
            placeholder="Misal: Ziarah Jabal Rahmah Jamaah VIP"
            placeholderTextColor={colors.textMuted}
            value={title}
            onChangeText={onChangeTitle}
          />
          <Text style={formStyles.fieldLabel}>Deskripsi (opsional)</Text>
          <TextInput
            style={[formStyles.input, formStyles.textarea]}
            placeholder="Ceritakan singkat pelayanan di foto ini..."
            placeholderTextColor={colors.textMuted}
            value={description}
            onChangeText={onChangeDescription}
            multiline
            textAlignVertical="top"
          />
          <Text style={formStyles.fieldLabel}>Foto album</Text>
          <PressableScale onPress={onPickImages} haptic="light">
            <View style={formStyles.pickBtn}>
              <Images size={20} color={colors.baytgo} strokeWidth={2} />
              <Text style={formStyles.pickBtnText}>
                {images.length > 0 ? `Tambah foto (${images.length}/10)` : 'Pilih foto dari galeri'}
              </Text>
            </View>
          </PressableScale>
          <UploadPreviewStrip
            files={images}
            onRemove={onRemoveImage}
            style={formStyles.previewStrip}
          />
          <Button
            label="Simpan album"
            onPress={onSubmit}
            loading={submitting}
            icon={<CloudUpload size={18} color={colors.white} strokeWidth={2} />}
          />
        </Card>
      ) : null}
    </>
  );
}

const formStyles = StyleSheet.create({
  formToggle: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: spacing.md },
  formToggleLeft: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, flex: 1 },
  formToggleIcon: {
    width: 40, height: 40, borderRadius: radius.sm, backgroundColor: colors.baytgoLight,
    alignItems: 'center', justifyContent: 'center',
  },
  formToggleTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  formToggleSub: { marginTop: 2, ...typography.small, color: colors.textSecondary },
  formCard: { marginBottom: spacing.xl },
  fieldLabel: { ...typography.label, color: colors.textSecondary, marginBottom: spacing.sm },
  input: {
    backgroundColor: colors.background, borderRadius: radius.sm, paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md + 1, marginBottom: spacing.md, ...typography.caption,
    color: colors.textPrimary, borderWidth: 1, borderColor: colors.border,
  },
  textarea: { minHeight: 88, textAlignVertical: 'top' },
  pickBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: spacing.sm,
    backgroundColor: colors.background, borderRadius: radius.sm, paddingVertical: spacing.lg,
    marginBottom: spacing.md, borderWidth: 1.5, borderColor: colors.baytgo, borderStyle: 'dashed',
  },
  pickBtnText: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  previewStrip: { marginBottom: spacing.md },
});
