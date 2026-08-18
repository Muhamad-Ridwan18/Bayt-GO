import React, { useCallback, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import ScreenHeader from '../components/ScreenHeader';
import { fetchTerms } from '../api/home';
import { ErrorState, SkeletonList } from '../ui';
import { colors, layout, spacing, typography } from '../theme/tokens';
import { useLocale } from '../utils/locale';

export default function TermsScreen({ navigation }) {
  const locale = useLocale(); const isEn = locale === 'en';
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const load = useCallback(async () => {
    try {
      const next = await fetchTerms();
      setData(next);
      setError(null);
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to load terms and conditions' : 'Gagal memuat syarat dan ketentuan'));
    } finally {
      setLoading(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  const sections = Array.isArray(data?.sections) ? data.sections : [];

  return (
    <View style={styles.container}>
      <ScreenHeader
        title={data?.title || (isEn ? 'Terms and conditions' : 'Syarat dan ketentuan')}
        onBack={() => navigation.goBack()}
      />
      {loading ? <SkeletonList count={4} style={styles.pad} /> : null}
      {error && !data ? <ErrorState description={error} onRetry={load} /> : null}
      {data ? (
        <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
          {data.intro ? <Text style={styles.intro}>{data.intro}</Text> : null}
          {sections.map((section, index) => (
            <View key={`${section.title || 'section'}-${index}`} style={styles.section}>
              {section.title ? <Text style={styles.sectionTitle}>{section.title}</Text> : null}
              {(section.paragraphs || []).map((paragraph) => (
                <Text key={paragraph} style={styles.paragraph}>{paragraph}</Text>
              ))}
              {(section.bullets || []).map((bullet) => (
                <View key={bullet} style={styles.bulletRow}>
                  <Text style={styles.bulletMark}>•</Text>
                  <Text style={styles.bulletText}>{bullet}</Text>
                </View>
              ))}
            </View>
          ))}
        </ScrollView>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  pad: { padding: layout.screenPadding },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['4xl'] },
  intro: {
    ...typography.caption,
    color: colors.textSecondary,
    lineHeight: 22,
    marginBottom: spacing.xl,
  },
  section: { marginBottom: spacing.xl },
  sectionTitle: {
    ...typography.subtitle,
    fontSize: 16,
    color: colors.textPrimary,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    marginBottom: spacing.sm,
  },
  paragraph: {
    ...typography.caption,
    color: colors.textSecondary,
    lineHeight: 22,
    marginBottom: spacing.sm,
  },
  bulletRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginBottom: spacing.sm,
  },
  bulletMark: {
    ...typography.caption,
    color: colors.baytgo,
    lineHeight: 22,
  },
  bulletText: {
    flex: 1,
    ...typography.caption,
    color: colors.textSecondary,
    lineHeight: 22,
  },
});
