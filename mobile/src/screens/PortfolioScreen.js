import React, { useCallback, useMemo, useState } from 'react';
import {
  View, Text, StyleSheet, ScrollView, RefreshControl, Alert,
  KeyboardAvoidingView, Platform,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import * as ImagePicker from 'expo-image-picker';
import {
  Camera, Images,
} from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import ImageLightbox from '../components/ImageLightbox';
import { fetchPortfolio, fetchPortfolioItem, createPortfolio, deletePortfolio } from '../api/portfolio';
import { useAuth } from '../context/AuthContext';
import { Card, EmptyState, SkeletonList } from '../ui';
import { AlbumCard, PortfolioCreateSection, StatCard } from '../features/portfolio/PortfolioScreenParts';
import { notifyError, notifySuccess } from '../utils/feedback';
import { colors, layout, spacing, typography } from '../theme/tokens';

export default function PortfolioScreen({ navigation }) {
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [deletingId, setDeletingId] = useState(null);
  const [previewingId, setPreviewingId] = useState(null);
  const [albums, setAlbums] = useState([]);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [images, setImages] = useState([]);
  const [lightbox, setLightbox] = useState({ visible: false, images: [], index: 0, title: '' });
  const [formOpen, setFormOpen] = useState(true);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchPortfolio(token);
      setAlbums(data.portfolios || []);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat memuat portofolio');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  const totalPhotos = useMemo(
    () => albums.reduce((sum, a) => sum + (a.images_count || 0), 0),
    [albums],
  );

  const pickImages = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('Izin diperlukan', 'Izinkan akses galeri untuk menambah foto.');
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsMultipleSelection: true,
      selectionLimit: 10,
      quality: 0.85,
    });

    if (!result.canceled && result.assets?.length) {
      setImages((prev) => [...prev, ...result.assets].slice(0, 10));
    }
  };

  const removePick = (index) => {
    setImages((prev) => prev.filter((_, i) => i !== index));
  };

  const handleAdd = async () => {
    if (!title.trim()) {
      Alert.alert('Validasi', 'Judul album wajib diisi.');
      return;
    }
    if (images.length === 0) {
      Alert.alert('Validasi', 'Pilih minimal satu foto.');
      return;
    }

    const formData = new FormData();
    formData.append('title', title.trim());
    if (description.trim()) formData.append('description', description.trim());
    images.forEach((img, index) => {
      formData.append(`images[${index}]`, {
        uri: img.uri,
        name: img.fileName || `portfolio-${index}.jpg`,
        type: img.mimeType || 'image/jpeg',
      });
    });

    setSubmitting(true);
    try {
      await createPortfolio(token, formData);
      notifySuccess('Album portofolio ditambahkan.');
      setTitle('');
      setDescription('');
      setImages([]);
      setFormOpen(false);
      await load(true);
    } catch (err) {
      notifyError(err.message || 'Tidak dapat menambah portofolio');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = (item) => {
    Alert.alert('Hapus album?', `Hapus "${item.title}"?`, [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Hapus',
        style: 'destructive',
        onPress: async () => {
          setDeletingId(item.id);
          try {
            await deletePortfolio(token, item.id);
            await load(true);
          } catch (err) {
            Alert.alert('Gagal', err.message || 'Tidak dapat menghapus album');
          } finally {
            setDeletingId(null);
          }
        },
      },
    ]);
  };

  const handlePreview = async (item) => {
    setPreviewingId(item.id);
    try {
      const data = await fetchPortfolioItem(token, item.id);
      const urls = (data.portfolio?.images || []).map((img) => img.url);
      if (urls.length === 0 && item.cover_url) urls.push(item.cover_url);
      if (urls.length === 0) {
        Alert.alert('Info', 'Album belum memiliki foto.');
        return;
      }
      setLightbox({ visible: true, images: urls, index: 0, title: item.title });
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat memuat foto album');
    } finally {
      setPreviewingId(null);
    }
  };

  return (
    <View style={styles.container}>
      <TabPageHeader title="Portofolio" subtitle="Galeri dokumentasi layanan Anda" />

      {loading && !refreshing ? (
        <SkeletonList count={3} style={styles.skeleton} />
      ) : (
        <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
          <ScrollView
            contentContainerStyle={styles.scroll}
            showsVerticalScrollIndicator={false}
            keyboardShouldPersistTaps="handled"
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
            }
          >
            <Card style={styles.infoBanner} padding={spacing.lg} elevated={false}>
              <Images size={18} color={colors.baytgo} strokeWidth={2} />
              <Text style={styles.infoText}>
                Unggah foto terbaik saat membimbing jamaah. Album ini tampil di profil publik Anda.
              </Text>
            </Card>

            <View style={styles.statsRow}>
              <StatCard label="Album" value={albums.length} Icon={Images} />
              <StatCard label="Total foto" value={totalPhotos} Icon={Camera} />
            </View>

            <PortfolioCreateSection
              formOpen={formOpen}
              onToggleForm={() => setFormOpen((v) => !v)}
              title={title}
              description={description}
              images={images}
              submitting={submitting}
              onChangeTitle={setTitle}
              onChangeDescription={setDescription}
              onPickImages={pickImages}
              onRemoveImage={removePick}
              onSubmit={handleAdd}
            />

            <Text style={styles.sectionTitle}>Album saya</Text>

            {albums.length === 0 ? (
              <EmptyState
                variant="package"
                title="Belum ada album"
                description="Tambahkan album pertama untuk menampilkan dokumentasi layanan ke jamaah."
              />
            ) : (
              <View style={styles.grid}>
                {albums.map((item) => (
                  <AlbumCard
                    key={String(item.id)}
                    item={item}
                    token={token}
                    onPreview={() => handlePreview(item)}
                    onEdit={() => navigation.navigate('PortfolioEdit', { portfolioId: item.id })}
                    onDelete={() => handleDelete(item)}
                    deleting={deletingId === item.id || previewingId === item.id}
                  />
                ))}
              </View>
            )}
          </ScrollView>
        </KeyboardAvoidingView>
      )}

      <ImageLightbox
        visible={lightbox.visible}
        images={lightbox.images}
        index={lightbox.index}
        title={lightbox.title}
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
  skeleton: { paddingHorizontal: layout.screenPadding, paddingTop: spacing.lg },
  scroll: { paddingHorizontal: layout.screenPadding, paddingTop: spacing.lg, paddingBottom: spacing['4xl'] },
  infoBanner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
    backgroundColor: colors.baytgoLight,
    borderColor: 'rgba(26,61,52,0.1)',
    marginBottom: spacing.md,
  },
  infoText: { flex: 1, ...typography.caption, lineHeight: 20, fontWeight: '600', color: colors.baytgo },
  statsRow: { flexDirection: 'row', gap: spacing.md, marginBottom: spacing.md },
  sectionTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo, marginBottom: spacing.md },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.md },
});
