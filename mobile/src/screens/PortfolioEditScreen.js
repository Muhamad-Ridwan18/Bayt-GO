import React, { useCallback, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TextInput, Alert,
  KeyboardAvoidingView, Platform,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { CheckCircle2 } from 'lucide-react-native';
import * as ImagePicker from 'expo-image-picker';
import ScreenHeader from '../components/ScreenHeader';
import ImageLightbox from '../components/ImageLightbox';
import { fetchPortfolioItem, updatePortfolio } from '../api/portfolio';
import { useAuth } from '../context/AuthContext';
import { Button, Card, SkeletonList } from '../ui';
import { GalleryGrid } from '../features/portfolio/PortfolioEditParts';
import { notifySuccessThen, notifyError } from '../utils/feedback';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

export default function PortfolioEditScreen({ navigation, route }) {
  const { portfolioId } = route.params;
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [images, setImages] = useState([]);
  const [deleteIds, setDeleteIds] = useState([]);
  const [newImages, setNewImages] = useState([]);
  const [lightbox, setLightbox] = useState({ visible: false, images: [], index: 0 });

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchPortfolioItem(token, portfolioId);
      const p = data.portfolio;
      setTitle(p.title || '');
      setDescription(p.description || '');
      setImages(p.images || []);
      setDeleteIds([]);
      setNewImages([]);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat memuat album');
      navigation.goBack();
    } finally {
      setLoading(false);
    }
  }, [token, portfolioId, navigation]);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  const pickNewImages = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('Izin diperlukan', 'Izinkan akses galeri.');
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsMultipleSelection: true,
      selectionLimit: 10,
      quality: 0.85,
    });
    if (!result.canceled && result.assets?.length) {
      setNewImages((prev) => [...prev, ...result.assets].slice(0, 10));
    }
  };

  const handleSave = async () => {
    if (!title.trim()) {
      Alert.alert('Validasi', 'Judul wajib diisi.');
      return;
    }

    const remaining = images.filter((img) => !deleteIds.includes(img.id)).length + newImages.length;
    if (remaining === 0) {
      Alert.alert('Validasi', 'Album harus memiliki minimal satu foto.');
      return;
    }

    setSaving(true);
    try {
      await updatePortfolio(token, portfolioId, {
        title: title.trim(),
        description: description.trim() || null,
        newImages,
        deleteImageIds: deleteIds,
      });
      notifySuccessThen(navigation, 'Album diperbarui.', () => navigation.goBack());
    } catch (err) {
      notifyError(err.message || 'Tidak dapat menyimpan');
    } finally {
      setSaving(false);
    }
  };

  const visibleImages = images.filter((img) => !deleteIds.includes(img.id));
  const allPreviewUrls = [
    ...visibleImages.map((img) => img.url),
    ...newImages.map((img) => img.uri),
  ];

  return (
    <View style={styles.container}>
      <ScreenHeader title="Edit album" subtitle={title || 'Kelola foto album'} onBack={() => navigation.goBack()} />

      {loading ? (
        <SkeletonList count={2} style={styles.skeleton} />
      ) : (
        <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
          <ScrollView
            contentContainerStyle={styles.scroll}
            showsVerticalScrollIndicator={false}
            keyboardShouldPersistTaps="handled"
          >
            <Card padding={spacing.lg} elevated={false}>
              <Text style={styles.fieldLabel}>Judul kegiatan</Text>
              <TextInput
                style={styles.input}
                placeholder="Judul album"
                placeholderTextColor={colors.textMuted}
                value={title}
                onChangeText={setTitle}
              />

              <Text style={styles.fieldLabel}>Deskripsi (opsional)</Text>
              <TextInput
                style={[styles.input, styles.textarea]}
                placeholder="Ceritakan singkat pelayanan..."
                placeholderTextColor={colors.textMuted}
                value={description}
                onChangeText={setDescription}
                multiline
                textAlignVertical="top"
              />
            </Card>

            <GalleryGrid
              visibleImages={visibleImages}
              newImages={newImages}
              token={token}
              allPreviewUrls={allPreviewUrls}
              onPreview={(urls, index) => setLightbox({ visible: true, images: urls, index })}
              onToggleDelete={(id) => setDeleteIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]))}
              onRemoveNew={(index) => setNewImages((prev) => prev.filter((_, i) => i !== index))}
              onPickNew={pickNewImages}
            />

            <Button
              label="Simpan perubahan"
              onPress={handleSave}
              loading={saving}
              icon={<CheckCircle2 size={18} color={colors.white} strokeWidth={2} />}
            />
          </ScrollView>
        </KeyboardAvoidingView>
      )}

      <ImageLightbox
        visible={lightbox.visible}
        images={lightbox.images}
        index={lightbox.index}
        title={title}
        token={token}
        onClose={() => setLightbox((s) => ({ ...s, visible: false }))}
        onChangeIndex={(idx) => setLightbox((s) => ({ ...s, index: idx }))}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  flex: { flex: 1 },
  skeleton: { padding: layout.screenPadding, paddingTop: spacing.lg },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['4xl'] },
  fieldLabel: { ...typography.label, color: colors.textSecondary, marginBottom: spacing.sm },
  input: {
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md + 1,
    marginBottom: spacing.md,
    ...typography.caption,
    color: colors.textPrimary,
    borderWidth: 1,
    borderColor: colors.border,
  },
  textarea: { minHeight: 88, textAlignVertical: 'top' },
});
