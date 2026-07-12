import React, { useCallback, useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Modal,
  Image,
  ActivityIndicator,
  ScrollView,
  Dimensions,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  AlertCircle,
  Expand,
  ExternalLink,
  File,
  FileText,
  FolderOpen,
  Map,
  Plane,
  Share,
  X,
  CreditCard,
} from 'lucide-react-native';
import { PressableScale } from '../ui';
import { colors, spacing, radius, typography } from '../theme/tokens';
import {
  downloadBookingDocument,
  shareBookingDocument,
} from '../utils/bookingDocuments';

const { width: SCREEN_W, height: SCREEN_H } = Dimensions.get('window');

const DOC_ICONS = {
  outbound: Plane,
  return: Plane,
  passport: CreditCard,
  itinerary: Map,
  visa: FileText,
};

function DocumentThumb({ token, bookingId, doc, onPress }) {
  const [loading, setLoading] = useState(true);
  const [preview, setPreview] = useState(null);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      try {
        const file = await downloadBookingDocument(token, bookingId, doc.type);
        if (!cancelled) {
          setPreview(file);
          setFailed(false);
        }
      } catch {
        if (!cancelled) setFailed(true);
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [token, bookingId, doc.type]);

  const IconComponent = DOC_ICONS[doc.type] || FileText;

  return (
    <PressableScale style={styles.thumbCard} onPress={() => onPress(doc, preview)} haptic="light">
      <View style={styles.thumbMedia}>
        {loading ? (
          <ActivityIndicator color={colors.baytgo} size="small" />
        ) : preview?.isImage ? (
          <Image source={{ uri: preview.uri }} style={styles.thumbImage} resizeMode="cover" />
        ) : (
          <View style={styles.pdfPlaceholder}>
            {failed ? (
              <AlertCircle size={28} color={colors.baytgo} strokeWidth={2} />
            ) : (
              <File size={28} color={colors.baytgo} strokeWidth={2} />
            )}
            <Text style={styles.pdfTag}>{failed ? 'Gagal' : 'PDF'}</Text>
          </View>
        )}
        <View style={styles.thumbOverlay}>
          <Expand size={14} color={colors.white} strokeWidth={2} />
        </View>
      </View>
      <View style={styles.thumbMeta}>
        <IconComponent size={14} color={colors.baytgo} strokeWidth={2} />
        <Text style={styles.thumbLabel} numberOfLines={2}>{doc.label}</Text>
      </View>
    </PressableScale>
  );
}

function PreviewModal({ visible, doc, preview, token, bookingId, onClose }) {
  const [loading, setLoading] = useState(false);
  const [file, setFile] = useState(preview);

  useEffect(() => {
    setFile(preview);
  }, [preview]);

  const ensureFile = useCallback(async () => {
    if (file?.uri) return file;
    setLoading(true);
    try {
      const downloaded = await downloadBookingDocument(token, bookingId, doc.type);
      setFile(downloaded);
      return downloaded;
    } finally {
      setLoading(false);
    }
  }, [file, token, bookingId, doc?.type]);

  const handleShare = async () => {
    try {
      const current = await ensureFile();
      await shareBookingDocument(current.uri, doc.label);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat membuka dokumen');
    }
  };

  if (!doc) return null;

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.modalBackdrop}>
        <SafeAreaView style={styles.modalSafe}>
          <View style={styles.modalTop}>
            <PressableScale style={styles.modalClose} onPress={onClose} hitSlop={8} haptic="light">
              <X size={22} color={colors.white} strokeWidth={2} />
            </PressableScale>
            <Text style={styles.modalTitle} numberOfLines={1}>{doc.label}</Text>
            <PressableScale style={styles.modalShare} onPress={handleShare} hitSlop={8} haptic="light">
              <Share size={20} color={colors.white} strokeWidth={2} />
            </PressableScale>
          </View>

          <View style={styles.modalBody}>
            {loading ? (
              <ActivityIndicator color={colors.white} size="large" />
            ) : file?.isImage ? (
              <ScrollView
                maximumZoomScale={3}
                minimumZoomScale={1}
                contentContainerStyle={styles.imageScroll}
                centerContent
              >
                <Image
                  source={{ uri: file.uri }}
                  style={styles.previewImage}
                  resizeMode="contain"
                />
              </ScrollView>
            ) : (
              <View style={styles.pdfPreview}>
                <View style={styles.pdfIconWrap}>
                  <FileText size={48} color={colors.baytgo} strokeWidth={2} />
                </View>
                <Text style={styles.pdfTitle}>{doc.label}</Text>
                <Text style={styles.pdfSub}>File PDF — ketuk tombol di bawah untuk membuka atau membagikan</Text>
                <PressableScale style={styles.openPdfBtn} onPress={handleShare} haptic="medium">
                  <ExternalLink size={18} color={colors.white} strokeWidth={2} />
                  <Text style={styles.openPdfText}>Buka dokumen</Text>
                </PressableScale>
              </View>
            )}
          </View>
        </SafeAreaView>
      </View>
    </Modal>
  );
}

export default function BookingDocumentGallery({ token, bookingId, documents, title = 'Dokumen jamaah' }) {
  const [previewDoc, setPreviewDoc] = useState(null);
  const [previewFile, setPreviewFile] = useState(null);

  if (!documents?.length) return null;

  const openPreview = (doc, file) => {
    setPreviewDoc(doc);
    setPreviewFile(file);
  };

  const closePreview = () => {
    setPreviewDoc(null);
    setPreviewFile(null);
  };

  return (
    <View style={styles.section}>
      <View style={styles.sectionHead}>
        <View style={styles.sectionIcon}>
          <FolderOpen size={18} color={colors.baytgo} strokeWidth={2} />
        </View>
        <View style={styles.sectionCopy}>
          <Text style={styles.sectionTitle}>{title}</Text>
          <Text style={styles.sectionSub}>{documents.length} file diunggah jamaah</Text>
        </View>
      </View>

      <View style={styles.grid}>
        {documents.map((doc) => (
          <DocumentThumb
            key={doc.type}
            token={token}
            bookingId={bookingId}
            doc={doc}
            onPress={openPreview}
          />
        ))}
      </View>

      <PreviewModal
        visible={!!previewDoc}
        doc={previewDoc}
        preview={previewFile}
        token={token}
        bookingId={bookingId}
        onClose={closePreview}
      />
    </View>
  );
}

const THUMB_W = (SCREEN_W - 40 - 12) / 2;

const styles = StyleSheet.create({
  section: {
    backgroundColor: colors.white,
    borderRadius: radius.md,
    padding: spacing.lg,
    marginBottom: spacing.md,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
  },
  sectionHead: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, marginBottom: spacing.md + 2 },
  sectionIcon: {
    width: 40,
    height: 40,
    borderRadius: radius.sm,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sectionCopy: { flex: 1 },
  sectionTitle: { ...typography.caption, fontSize: 15, fontWeight: '900', color: colors.baytgo },
  sectionSub: { marginTop: 2, ...typography.caption, fontWeight: '600', color: colors.textSecondary },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.md },
  thumbCard: {
    width: THUMB_W,
    borderRadius: radius.sm,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: colors.surface,
    backgroundColor: colors.canvas,
  },
  thumbMedia: {
    height: 108,
    backgroundColor: colors.surface,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  thumbImage: { width: '100%', height: '100%' },
  pdfPlaceholder: { alignItems: 'center', gap: spacing.xs },
  pdfTag: { ...typography.small, fontWeight: '800', color: colors.baytgo },
  thumbOverlay: {
    position: 'absolute',
    right: spacing.sm,
    bottom: spacing.sm,
    width: 26,
    height: 26,
    borderRadius: 13,
    backgroundColor: 'rgba(15,46,40,0.55)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  thumbMeta: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm - 2,
    padding: spacing.sm + 2,
  },
  thumbLabel: { flex: 1, ...typography.caption, fontWeight: '700', color: colors.slate700, lineHeight: 16 },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.92)' },
  modalSafe: { flex: 1 },
  modalTop: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm + 2,
    gap: spacing.sm + 2,
  },
  modalClose: {
    width: 40,
    height: 40,
    borderRadius: radius.full,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalShare: {
    width: 40,
    height: 40,
    borderRadius: radius.full,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalTitle: { flex: 1, ...typography.body, fontWeight: '800', color: colors.white, textAlign: 'center' },
  modalBody: { flex: 1, justifyContent: 'center' },
  imageScroll: { flexGrow: 1, justifyContent: 'center', minHeight: SCREEN_H * 0.65 },
  previewImage: { width: SCREEN_W, height: SCREEN_H * 0.65 },
  pdfPreview: { alignItems: 'center', paddingHorizontal: spacing['3xl'] },
  pdfIconWrap: {
    width: 88,
    height: 88,
    borderRadius: radius.md,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
  },
  pdfTitle: { ...typography.subtitle, fontWeight: '900', color: colors.white, textAlign: 'center' },
  pdfSub: {
    marginTop: spacing.sm,
    ...typography.caption,
    lineHeight: 20,
    fontWeight: '600',
    color: 'rgba(255,255,255,0.7)',
    textAlign: 'center',
  },
  openPdfBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing['2xl'],
    backgroundColor: colors.baytgo,
    paddingHorizontal: spacing.xl,
    paddingVertical: spacing.md + 2,
    borderRadius: radius.sm,
  },
  openPdfText: { ...typography.caption, fontWeight: '800', color: colors.white },
});
