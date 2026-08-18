import React from 'react';
import { ScrollView, StyleSheet, Text, View, Linking } from 'react-native';
import { Calendar, FileText, LogIn, MessageCircle, Newspaper, Search, User } from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import { navigateRoot } from '../navigation/rootNavigation';
import Button from '../ui/Button';
import Card from '../ui/Card';
import PressableScale from '../ui/PressableScale';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { useBrand } from '../context/BrandContext';
import LanguageSwitcher from '../components/LanguageSwitcher';
import { useLocale } from '../utils/locale';

const FEATURES = [
  { icon: Search, title: 'Cari Muthowif', sub: 'Temukan pendamping ibadah terpercaya' },
  { icon: Calendar, title: 'Kelola Booking', sub: 'Pantau pesanan dan jadwal perjalanan' },
  { icon: MessageCircle, title: 'Chat Langsung', sub: 'Chat tersedia setelah booking dikonfirmasi dan pembayaran berhasil.' },
];

export default function ProfileGuestScreen({ navigation }) {
  const { contactUrl } = useBrand();
  const locale = useLocale();
  const isEn = locale === 'en';

  const tabTitle = isEn ? 'Profile' : 'Profil';
  const tabSubtitle = isEn ? 'Sign in to manage your account' : 'Masuk untuk mengelola akun Anda';
  const heroTitle = isEn ? 'Welcome to BaytGo' : 'Selamat datang di BaytGo';
  const heroSub = isEn
    ? 'Sign in or register to book muthowif and manage your worship travel.'
    : 'Masuk atau daftar untuk memesan muthowif dan mengelola perjalanan ibadah Anda.';
  const btnSignIn = isEn ? 'Sign in' : 'Masuk';
  const btnRegisterCustomer = isEn ? 'Register as Pilgrim' : 'Daftar sebagai Jamaah';
  const btnRegisterMuthowif = isEn ? 'Register as Muthowif' : 'Daftar sebagai Muthowif';
  const canDoTitle = isEn ? 'What you can do' : 'Yang bisa Anda lakukan';
  const linkArticles = isEn ? 'Articles' : 'Artikel';
  const linkContact = isEn ? 'Contact us' : 'Hubungi kami';
  const linkTerms = isEn ? 'Terms & Conditions' : 'Syarat & Ketentuan';

  return (
    <View style={styles.container}>
      <TabPageHeader title={tabTitle} subtitle={tabSubtitle} />

      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        <Card style={styles.heroCard} padding={spacing['2xl']} elevated>
          <View style={styles.avatar}>
            <User size={40} color={colors.gold} strokeWidth={1.8} />
          </View>
          <Text style={styles.title}>{heroTitle}</Text>
          <Text style={styles.sub}>{heroSub}</Text>

          <View style={styles.primaryBtn}>
            <Button
              label={btnSignIn}
              onPress={() => navigateRoot(navigation, 'Login')}
              icon={<LogIn size={18} color={colors.white} strokeWidth={2} />}
            />
          </View>

          <View style={styles.secondaryBtn}>
            <Button
              label={btnRegisterCustomer}
              onPress={() => navigateRoot(navigation, 'Register', { role: 'customer' })}
              variant="secondary"
            />
          </View>

          <PressableScale
            onPress={() => navigateRoot(navigation, 'Register', { role: 'muthowif' })}
            haptic="light"
          >
            <Text style={styles.link}>{btnRegisterMuthowif} ›</Text>
          </PressableScale>
        </Card>

        <Text style={styles.sectionTitle}>{canDoTitle}</Text>
        {FEATURES.map((feat) => {
          const Icon = feat.icon;
          return (
            <Card key={feat.title} style={styles.featureRow} padding={spacing.lg} elevated={false} variant="flat">
              <View style={styles.featureInner}>
                <View style={styles.featureIcon}>
                  <Icon size={20} color={colors.baytgo} strokeWidth={2} />
                </View>
                <View style={styles.featureCopy}>
                  <Text style={styles.featureTitle}>{feat.title}</Text>
                  <Text style={styles.featureSub}>{feat.sub}</Text>
                </View>
              </View>
            </Card>
          );
        })}

        <View style={styles.langWrap}>
          <LanguageSwitcher />
        </View>

        <View style={styles.links}>
          <PressableScale
            onPress={() => navigation.getParent()?.navigate('HomeTab', { screen: 'ArticlesList' })}
            haptic="light"
            style={styles.termsBtn}
          >
            <Newspaper size={16} color={colors.baytgo} strokeWidth={2} />
            <Text style={styles.termsText}>{linkArticles}</Text>
          </PressableScale>
          {contactUrl ? (
            <PressableScale
              onPress={() => Linking.openURL(contactUrl)}
              haptic="light"
              style={styles.termsBtn}
            >
              <MessageCircle size={16} color={colors.baytgo} strokeWidth={2} />
              <Text style={styles.termsText}>{linkContact}</Text>
            </PressableScale>
          ) : null}
          <PressableScale
            onPress={() => navigation.getParent()?.navigate('HomeTab', { screen: 'Terms' })}
            haptic="light"
            style={styles.termsBtn}
          >
            <FileText size={16} color={colors.baytgo} strokeWidth={2} />
            <Text style={styles.termsText}>{linkTerms}</Text>
          </PressableScale>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: {
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.lg,
    paddingBottom: spacing.lg,
  },
  heroCard: {
    alignItems: 'center',
    marginBottom: spacing['2xl'],
    borderRadius: radius.md,
  },
  avatar: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
    borderWidth: 3,
    borderColor: colors.goldLight,
  },
  title: {
    ...typography.subtitle,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.baytgo,
    textAlign: 'center',
  },
  sub: {
    marginTop: spacing.sm,
    ...typography.caption,
    lineHeight: 21,
    color: colors.textSecondary,
    textAlign: 'center',
  },
  primaryBtn: { marginTop: spacing.xl },
  secondaryBtn: { marginTop: spacing.md },
  link: {
    marginTop: spacing.lg,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.goldMuted,
  },
  sectionTitle: {
    ...typography.label,
    color: colors.textSecondary,
    textTransform: 'uppercase',
    marginBottom: spacing.md,
    marginLeft: spacing.xs,
  },
  featureRow: {
    marginBottom: spacing.md,
    borderRadius: radius.md,
  },
  featureInner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  featureIcon: {
    width: 40,
    height: 40,
    borderRadius: radius.sm,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  featureCopy: { flex: 1 },
  featureTitle: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  featureSub: {
    marginTop: spacing.xs,
    ...typography.small,
    fontWeight: '600',
    fontFamily: 'PlusJakartaSans_600SemiBold',
    color: colors.textSecondary,
    lineHeight: 17,
  },
  links: { marginTop: spacing.lg, marginBottom: spacing.md, gap: spacing.xs },
  langWrap: { marginTop: spacing.xl },
  termsBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.md,
  },
  termsText: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.baytgo,
  },
});
