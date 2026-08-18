import React, { useCallback, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { useFocusEffect } from '@react-navigation/native';
import ScreenHeader from '../components/ScreenHeader';
import { fetchArticles } from '../api/home';
import { EmptyState, ErrorState, PressableScale, SkeletonList } from '../ui';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { resolveMediaUrl } from '../utils/mediaUrl';
import { useLocale } from '../utils/locale';

export default function ArticlesListScreen({ navigation }) {
  const locale = useLocale(); const isEn = locale === 'en';
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);
    try {
      const data = await fetchArticles();
      setArticles(data.data || data || []);
      setError(null);
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to load articles' : 'Gagal memuat artikel'));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  return (
    <View style={styles.container}>
      <ScreenHeader title={isEn ? 'Articles & Guides' : 'Artikel & Panduan'} onBack={() => navigation.goBack()} />
      {loading && !refreshing ? <SkeletonList count={4} style={styles.pad} /> : null}
      {error && !articles.length ? <ErrorState description={error} onRetry={() => load()} /> : null}
      {!loading && !error && articles.length === 0 ? (
        <EmptyState title={isEn ? 'No articles yet' : 'Belum ada artikel'} description={isEn ? 'Latest articles will appear here.' : 'Artikel terbaru akan muncul di sini.'} />
      ) : null}
      {articles.length ? (
        <ScrollView
          contentContainerStyle={styles.scroll}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />}
        >
          {articles.map((item) => {
            const thumb = resolveMediaUrl(item.thumbnail);
            return (
              <PressableScale
                key={item.slug}
                onPress={() => navigation.navigate('ArticleDetail', { slug: item.slug, preview: item })}
                haptic="light"
              >
                <View style={styles.card}>
                  <View style={styles.thumb}>
                    {thumb ? <Image source={{ uri: thumb }} style={styles.thumbImg} contentFit="cover" /> : null}
                  </View>
                  <View style={styles.body}>
                    <Text style={styles.title} numberOfLines={2}>{item.title}</Text>
                    {item.excerpt ? (
                      <Text style={styles.excerpt} numberOfLines={2}>{item.excerpt}</Text>
                    ) : null}
                  </View>
                </View>
              </PressableScale>
            );
          })}
        </ScrollView>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  pad: { padding: layout.screenPadding },
  scroll: { padding: layout.screenPadding, gap: spacing.md, paddingBottom: spacing['4xl'] },
  card: {
    flexDirection: 'row',
    backgroundColor: colors.card,
    borderRadius: radius.md - 2,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: 'rgba(226, 232, 240, 0.8)',
  },
  thumb: { width: 108, height: 96, backgroundColor: colors.baytgoLight },
  thumbImg: { width: '100%', height: '100%' },
  body: { flex: 1, padding: spacing.md, justifyContent: 'center' },
  title: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  excerpt: {
    marginTop: spacing.xs,
    ...typography.small,
    color: colors.textSecondary,
    lineHeight: 16,
  },
});
