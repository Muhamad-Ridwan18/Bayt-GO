import React, { useCallback, useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Modal,
  Image,
  ActivityIndicator,
  ScrollView,
  Dimensions,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import {
  downloadBookingDocument,
  shareBookingDocument,
} from '../utils/bookingDocuments';

const { width: SCREEN_W, height: SCREEN_H } = Dimensions.get('window');

const DOC_ICONS = {
  outbound: 'airplane-outline',
  return: 'airplane-outline',
  passport: 'card-outline',
  itinerary: 'map-outline',
  visa: 'document-text-outline',
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

  const icon = DOC_ICONS[doc.type] || 'document-text-outline';

  return (
    <TouchableOpacity style={styles.thumbCard} onPress={() => onPress(doc, preview)} activeOpacity={0.9}>
      <View style={styles.thumbMedia}>
        {loading ? (
          <ActivityIndicator color={colors.baytgo} size="small" />
        ) : preview?.isImage ? (
          <Image source={{ uri: preview.uri }} style={styles.thumbImage} resizeMode="cover" />
        ) : (
          <View style={styles.pdfPlaceholder}>
            <Ionicons name={failed ? 'alert-circle-outline' : 'document-outline'} size={28} color={colors.baytgo} />
            <Text style={styles.pdfTag}>{failed ? 'Gagal' : 'PDF'}</Text>
          </View>
        )}
        <View style={styles.thumbOverlay}>
          <Ionicons name="expand-outline" size={14} color={colors.white} />
        </View>
      </View>
      <View style={styles.thumbMeta}>
        <Ionicons name={icon} size={14} color={colors.baytgo} />
        <Text style={styles.thumbLabel} numberOfLines={2}>{doc.label}</Text>
      </View>
    </TouchableOpacity>
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
            <TouchableOpacity style={styles.modalClose} onPress={onClose} hitSlop={8}>
              <Ionicons name="close" size={22} color={colors.white} />
            </TouchableOpacity>
            <Text style={styles.modalTitle} numberOfLines={1}>{doc.label}</Text>
            <TouchableOpacity style={styles.modalShare} onPress={handleShare} hitSlop={8}>
              <Ionicons name="share-outline" size={20} color={colors.white} />
            </TouchableOpacity>
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
                  <Ionicons name="document-text" size={48} color={colors.baytgo} />
                </View>
                <Text style={styles.pdfTitle}>{doc.label}</Text>
                <Text style={styles.pdfSub}>File PDF — ketuk tombol di bawah untuk membuka atau membagikan</Text>
                <TouchableOpacity style={styles.openPdfBtn} onPress={handleShare} activeOpacity={0.9}>
                  <Ionicons name="open-outline" size={18} color={colors.white} />
                  <Text style={styles.openPdfText}>Buka dokumen</Text>
                </TouchableOpacity>
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
          <Ionicons name="folder-open-outline" size={18} color={colors.baytgo} />
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
    borderRadius: 20,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
  },
  sectionHead: { flexDirection: 'row', alignItems: 'center', gap: 12, marginBottom: 14 },
  sectionIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sectionCopy: { flex: 1 },
  sectionTitle: { fontSize: 15, fontWeight: '900', color: colors.baytgo },
  sectionSub: { marginTop: 2, fontSize: 12, fontWeight: '600', color: colors.slate500 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  thumbCard: {
    width: THUMB_W,
    borderRadius: 14,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: colors.slate100,
    backgroundColor: colors.canvas,
  },
  thumbMedia: {
    height: 108,
    backgroundColor: colors.slate100,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  thumbImage: { width: '100%', height: '100%' },
  pdfPlaceholder: { alignItems: 'center', gap: 4 },
  pdfTag: { fontSize: 11, fontWeight: '800', color: colors.baytgo },
  thumbOverlay: {
    position: 'absolute',
    right: 8,
    bottom: 8,
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
    gap: 6,
    padding: 10,
  },
  thumbLabel: { flex: 1, fontSize: 12, fontWeight: '700', color: colors.slate700, lineHeight: 16 },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.92)' },
  modalSafe: { flex: 1 },
  modalTop: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 10,
    gap: 10,
  },
  modalClose: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalShare: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalTitle: { flex: 1, fontSize: 16, fontWeight: '800', color: colors.white, textAlign: 'center' },
  modalBody: { flex: 1, justifyContent: 'center' },
  imageScroll: { flexGrow: 1, justifyContent: 'center', minHeight: SCREEN_H * 0.65 },
  previewImage: { width: SCREEN_W, height: SCREEN_H * 0.65 },
  pdfPreview: { alignItems: 'center', paddingHorizontal: 32 },
  pdfIconWrap: {
    width: 88,
    height: 88,
    borderRadius: 22,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  pdfTitle: { fontSize: 18, fontWeight: '900', color: colors.white, textAlign: 'center' },
  pdfSub: {
    marginTop: 8,
    fontSize: 13,
    lineHeight: 20,
    fontWeight: '600',
    color: 'rgba(255,255,255,0.7)',
    textAlign: 'center',
  },
  openPdfBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginTop: 24,
    backgroundColor: colors.baytgo,
    paddingHorizontal: 20,
    paddingVertical: 14,
    borderRadius: 14,
  },
  openPdfText: { fontSize: 14, fontWeight: '800', color: colors.white },
});
