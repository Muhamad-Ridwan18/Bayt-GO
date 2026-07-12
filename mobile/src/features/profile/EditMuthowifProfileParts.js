import React from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { Camera, CirclePlus, FileText, Trash2 } from 'lucide-react-native';
import { Image } from 'expo-image';
import { Card, PressableScale, SingleImagePreview } from '../../ui';
import { colors, radius, spacing, typography } from '../../theme/tokens';
import { isImageUpload } from '../../utils/uploadPreview';
import { resolveMediaUrl } from '../../utils/mediaUrl';

export function UploadField({ label, imageUrl, localUri, uploading, onPick, onClear }) {
  const uri = localUri || (imageUrl ? resolveMediaUrl(imageUrl) : null);

  return (
    <View style={styles.uploadField}>
      <Text style={styles.uploadLabel}>{label}</Text>
      {uri ? (
        <View style={styles.previewWrap}>
          <SingleImagePreview uri={uri} onRemove={onClear} size={120} />
          {uploading ? (
            <View style={styles.uploadOverlay}>
              <ActivityIndicator color={colors.white} />
            </View>
          ) : null}
        </View>
      ) : (
        <PressableScale onPress={onPick} disabled={uploading} haptic="light">
          <Card style={styles.uploadBtn} padding={0} elevated={false}>
            <View style={styles.uploadPlaceholder}>
              <Camera size={28} color={colors.textMuted} strokeWidth={1.8} />
              <Text style={styles.uploadPlaceholderText}>Pilih foto</Text>
            </View>
            {uploading ? (
              <View style={styles.uploadOverlay}>
                <ActivityIndicator color={colors.white} />
              </View>
            ) : null}
          </Card>
        </PressableScale>
      )}
      {uri ? (
        <PressableScale onPress={onPick} disabled={uploading} haptic="light" style={styles.changeBtn}>
          <Text style={styles.changeText}>{uploading ? 'Mengunggah…' : 'Ganti foto'}</Text>
        </PressableScale>
      ) : null}
    </View>
  );
}

export function DocumentRow({ doc, onDelete, deleting }) {
  const url = doc.url ? resolveMediaUrl(doc.url) : doc.uri;
  const showImage = url && (isImageUpload(doc) || doc.url);

  return (
    <View style={styles.docRow}>
      {showImage ? (
        <Image source={{ uri: url }} style={styles.docThumb} contentFit="cover" transition={200} />
      ) : (
        <View style={[styles.docThumb, styles.docThumbPlaceholder]}>
          <FileText size={20} color={colors.textMuted} strokeWidth={2} />
        </View>
      )}
      <Text style={styles.docName} numberOfLines={2}>
        {doc.name || 'Dokumen pendukung'}
      </Text>
      <PressableScale onPress={() => onDelete(doc)} disabled={deleting} haptic="light">
        <Trash2 size={20} color={colors.error} strokeWidth={2} />
      </PressableScale>
    </View>
  );
}

export function DocumentsSection({ documents, uploadingDoc, onAdd, onDelete, deletingDocId }) {
  return (
    <Card style={styles.docsSection} padding={spacing.lg} elevated={false}>
      <View style={styles.docsHeader}>
        <Text style={styles.uploadLabel}>Dokumen pendukung</Text>
        <PressableScale onPress={onAdd} disabled={uploadingDoc} haptic="light" style={styles.addDocBtn}>
          {uploadingDoc ? (
            <ActivityIndicator color={colors.baytgo} size="small" />
          ) : (
            <>
              <CirclePlus size={18} color={colors.baytgo} strokeWidth={2} />
              <Text style={styles.addDocText}>Tambah</Text>
            </>
          )}
        </PressableScale>
      </View>
      {documents.length === 0 ? (
        <Text style={styles.docsEmpty}>Belum ada dokumen pendukung.</Text>
      ) : (
        documents.map((doc) => (
          <DocumentRow
            key={String(doc.id || doc.uri)}
            doc={doc}
            onDelete={onDelete}
            deleting={deletingDocId === doc.id}
          />
        ))
      )}
    </Card>
  );
}

const styles = StyleSheet.create({
  uploadField: { marginBottom: spacing.lg },
  uploadLabel: { ...typography.label, color: colors.textSecondary, marginBottom: spacing.sm },
  uploadBtn: { borderRadius: radius.md, overflow: 'hidden' },
  previewWrap: { position: 'relative', alignSelf: 'flex-start' },
  uploadPlaceholder: {
    height: 140,
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
  },
  uploadPlaceholderText: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.textSecondary },
  uploadOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.35)',
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: radius.sm,
  },
  changeBtn: { marginTop: spacing.sm },
  changeText: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.baytgo },
  docsSection: { marginBottom: spacing.lg },
  docsHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: spacing.md },
  addDocBtn: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs },
  addDocText: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  docsEmpty: { ...typography.caption, color: colors.textSecondary, fontWeight: '600' },
  docRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  docThumb: { width: 44, height: 44, borderRadius: radius.sm - 2, backgroundColor: colors.surface },
  docThumbPlaceholder: { alignItems: 'center', justifyContent: 'center' },
  docName: { flex: 1, ...typography.caption, color: colors.slate700, fontWeight: '600' },
});
