import React, { useCallback, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { useFocusEffect } from '@react-navigation/native';
import ScreenHeader from '../components/ScreenHeader';
import { fetchArticle } from '../api/home';
import { ErrorState, SkeletonList } from '../ui';
import { colors, layout, spacing, typography } from '../theme/tokens';
import { resolveMediaUrl } from '../utils/mediaUrl';

export default function ArticleDetailScreen({ navigation, route }) {
  const { slug, preview } = route.params || {};
  const [article, setArticle] = useState(preview || null);
  const [loading, setLoading] = useState(!preview);
  const [error, setError] = useState(null);

  const load = useCallback(async () => {
    if (!slug) return;
    try {
      const data = await fetchArticle(slug);
      setArticle(data);
      setError(null);
    } catch (err) {
      setError(err.message || 'Artikel tidak ditemukan');
    } finally {
      setLoading(false);
    }
  }, [slug]);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  const thumb = resolveMediaUrl(article?.thumbnail);
  const published = article?.published_at
    ? new Date(article.published_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
    : null;

  return (
    <View style={styles.container}>
      <ScreenHeader title="Artikel" onBack={() => navigation.goBack()} />
      {loading ? <SkeletonList count={3} style={styles.pad} /> : null}
      {error && !article ? <ErrorState description={error} onRetry={load} /> : null}
      {article ? (
        <ScrollView contentContainerStyle={styles.scroll}>
          {article.category ? <Text style={styles.category}>{article.category}</Text> : null}
          <Text style={styles.title}>{article.title}</Text>
          {article.excerpt ? <Text style={styles.excerpt}>{article.excerpt}</Text> : null}
          <Text style={styles.meta}>
            {[article.author, published, article.reading_minutes ? `${article.reading_minutes} menit baca` : null]
              .filter(Boolean)
              .join(' · ')}
          </Text>
          {thumb ? <Image source={{ uri: thumb }} style={styles.hero} contentFit="cover" /> : null}
          {(article.images || []).filter((uri) => uri !== thumb).map((uri) => (
            <Image key={uri} source={{ uri: resolveMediaUrl(uri) }} style={styles.inlineImg} contentFit="cover" />
          ))}
          {article.body ? <Text style={styles.body}>{article.body}</Text> : null}
        </ScrollView>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  pad: { padding: layout.screenPadding },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['4xl'] },
  category: {
    ...typography.label,
    color: colors.baytgo,
    textTransform: 'uppercase',
    marginBottom: spacing.sm,
  },
  title: {
    ...typography.title,
    fontSize: 22,
    color: colors.textPrimary,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  excerpt: {
    marginTop: spacing.md,
    ...typography.caption,
    color: colors.textSecondary,
    lineHeight: 22,
  },
  meta: {
    marginTop: spacing.md,
    ...typography.small,
    color: colors.textMuted,
  },
  hero: {
    marginTop: spacing.lg,
    width: '100%',
    height: 180,
    borderRadius: 20,
    backgroundColor: colors.card,
  },
  inlineImg: {
    marginTop: spacing.md,
    width: '100%',
    height: 180,
    borderRadius: 16,
    backgroundColor: colors.card,
  },
  body: {
    marginTop: spacing.lg,
    ...typography.caption,
    color: colors.textPrimary,
    lineHeight: 22,
  },
});
