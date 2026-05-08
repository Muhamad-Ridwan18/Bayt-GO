<?php

declare(strict_types=1);

namespace App\Support;

use Database\Seeders\ArticleSeeder;

/**
 * Source markdown for seeded “journal” articles — converted to HTML in {@see ArticleSeeder}.
 *
 * @internal
 */
final class ArticleSeedDefinitions
{
    /**
     * @return list<array{
     *     slug: string,
     *     is_featured: bool,
     *     sort_order: int,
     *     locales: array<string, array{title: string, excerpt: string, category: string, author: string, body_md: string}>
     * }>
     */
    public static function articles(): array
    {
        return [
            [
                'slug' => 'panduan-memilih-muthowif-di-marketplace',
                'is_featured' => true,
                'sort_order' => 0,
                'locales' => [
                    'id' => [
                        'title' => 'Panduan memilih muthowif di marketplace era digital',
                        'excerpt' => 'Kerangka praktis menilai komunikasi, jadwal, dan bukti reputasi — sebelum Anda menekan tombol konfirmasi booking.',
                        'category' => 'Panduan',
                        'author' => 'Tim BaytGo',
                        'body_md' => self::guideId(),
                    ],
                    'en' => [
                        'title' => 'How to choose a muthowif on a modern marketplace',
                        'excerpt' => 'A practical framework for comparing communication clarity, calendars, and proof of experience before you confirm a booking.',
                        'category' => 'Guide',
                        'author' => 'BaytGo Editorial',
                        'body_md' => self::guideEn(),
                    ],
                    'ar' => [
                        'title' => 'كيف تختار مطوّفك في منصة حديثة',
                        'excerpt' => 'إطار عملي للمقارنة بين التواصل، الجداول، وثقة الأدلة قبل تأكيد الحجز.',
                        'category' => 'دليل',
                        'author' => 'فريق BaytGo',
                        'body_md' => self::guideAr(),
                    ],
                ],
            ],
            [
                'slug' => 'keamanan-pembayaran-dan-kejelasan-di-baytgo',
                'is_featured' => false,
                'sort_order' => 1,
                'locales' => [
                    'id' => [
                        'title' => 'Keamanan pembayaran & kejelasan yang bisa Anda perkirakan',
                        'excerpt' => 'Apa arti “harga transparan” di layanan pendamping, bagaimana alur pembayaran berjalan, dan apa yang wajar Anda tanyakan ke muthowif.',
                        'category' => 'Kepercayaan',
                        'author' => 'Tim BaytGo',
                        'body_md' => self::trustId(),
                    ],
                    'en' => [
                        'title' => 'Payment safety and predictable clarity on BaytGo',
                        'excerpt' => 'What transparent pricing truly means for companion services, how checkout fits into your itinerary, and the questions worth asking upfront.',
                        'category' => 'Trust',
                        'author' => 'BaytGo Editorial',
                        'body_md' => self::trustEn(),
                    ],
                    'ar' => [
                        'title' => 'أمان الدفع وضوح متوقَّع مع BaytGo',
                        'excerpt' => 'ماذا تعني الشفافة في الأسعار في خدمات المرافقة، وكيف يدفع الحجز في رحلتك.',
                        'category' => 'ثقة',
                        'author' => 'فريق BaytGo',
                        'body_md' => self::trustAr(),
                    ],
                ],
            ],
        ];
    }

    private static function guideId(): string
    {
        return <<<'MD'
Komposisi pendamping sangat memengaruhi ritme spiritual dan logistik satu rombongan. Marketplace profesional menghubungkan jamaah dengan muthowif berprofil konsisten — tanpa menghilangkan tugas Anda untuk bertanya secara spesifik.

## Matriks kebutuhan rombongan

Sebelum mencari nama, gambarkan profil kelompok: rentang umur, kebutuhan kursi roda, preferensi bahasa, durasi masa tinggal di kota suci, dan toleransi akan jeda jadwal. Profil itu menjadi filter pertama — bukan sekadar “siapa paling populer”.

## Komunikasi yang menenangkan

Tanyakan format _briefing_ harian, saluran darurat, dan bagaimana muthowif menangani perubahan kecil (terminal bus, keterlambatan penerbangan). Respons yang jelas dalam 24 jam pertama biasanya berkorelasi kuat dengan pengalaman di lapangan.

## Kalender & batasan waktu

Pastikan tanggal yang Anda inginkan memang tersedia pada tarif yang dipublikasikan. Marketplace yang baik memperlihatkan tanggal terblokir — ini melindungi kedua pihak dari janji yang tidak realistis.

## Bukti yang layak diminta

Sertifikasi formal, rekam jejak ibadah haji/umrah sebelumnya, dan referensi singkat (tanpa membocorkan data pribadi jamaah lain) adalah sinyal pelengkap. Gabungkan dengan ulasan setelah layanan selesai agar ekosistem tetap jujur.

> **Inti profesionalisme:** hindari transaksi pembayaran di luar alur yang disediakan platform; ini melindungi jadwal Anda dan memastikan ada jejak dukungan jika situasi berubah.

## Menutup dengan keputusan yang tenang

Ringkas pertanyaan Anda, bandingkan dua profil teratas berdasarkan kriteria yang sama, lalu amankan tanggal Anda. Ritual utama layak didampingi orang yang Anda percayai — proses seleksi yang struktural membantu Anda sampai di sana lebih yakin.

MD;
    }

    private static function guideEn(): string
    {
        return <<<'MD'
The right companion changes the emotional cadence of a group. A professional marketplace profiles muthowif consistently — but you still own the short list of questions that protect your itinerary.

## Start with the group brief

Capture age range, mobility needs, language preference, number of days in each city, and how much schedule slack you can tolerate. That brief is your first filter — not follower counts.

## Evaluate communication hygiene

Ask how daily briefings run, what the escalation path is when transport shifts, and how quickly you should expect answers during booking. Clear first replies often predict calmer coordination on the ground.

## Respect the calendar truth

Transparent marketplaces expose blocked dates for a reason — they align expectations before money moves. Confirm the rate you saw online still matches the dates you need.

## Evidence that matters

Formal credentials matter, yet so does recent experience leading similar cohorts. Reviews after completed trips keep the marketplace honest — leave one when yours wraps.

> **Professional tip:** keep payments inside the platform flow you were offered at checkout — it preserves dispute tooling and protects both sides when plans shift.

## Decide without theatrics

Compare two finalists against the same checklist, commit to dates, and move on. Confidence is a feature of good process — not luck.

MD;
    }

    private static function guideAr(): string
    {
        return <<<'MD'
اختيار المرافق يغيّر إيقاع رحلتك. تساعدك المنصّة على المقارنة، لكن الأسئلة الدقيقة تبقى مسؤوليتك.

## صف مجموعتك أولًا

حدّد الأعمار، اللغة، الحركة، ومدة كل مرحلة. هذا يقلّل الخيارات إلى ما يلائمكم فعلًا.

## التواصل والجدول

اسأل عن نقاط الاتصال عند التغيير، ووقت الاستجابة المتوقع. الجدول الواضح يمنع سوء الفهم لاحقًا.

## الأدلة والمراجعة

الشهادات المهمة، مع تقييمات بعد اكتمال الخدمة، تبني ثقة متبادلة لكل الجماعات القادمة.

> **تنبيه:** أبقِ الدفع ضمن مسار المنصة لحماية حقك عند تغيّر الجدول.

MD;
    }

    private static function trustId(): string
    {
        return <<<'MD'
Umrah bersama pendamping profesional menyatukan layanan manusia dengan logistik pembayaran digital. Memahami titik kontrol ini membuat Anda lebih tenang ketika membuka invoice.

## Harga transparan itu apa?

Artinya struktur tarif harian atau paket tertentu terlihat sebelum Anda berkomitmen, termasuk apa yang mengubah total (tamu tambahan, hari tambahan, atau tambahan lintas kota). Jika ada biaya opsional, ia seharusnya terlabel — bukan “muncul tiba-tiba”.

## Alur pembayaran yang wajar

Di BaytGo, alur checkout dirancang agar pembayaran terhubung pada permintaan booking yang sudah Anda setujui. Setelah muthowif merespons sesuai slot yang dipublikasikan, status transaksi dapat dilacak dari akun Anda — mirip cara Anda melacak pesanan marketplace lain yang serius tentang SLA.

## Data pribadi & percakapan

Profil menjelaskan keahlian muthowif, sementara detail sensitif jamaah sebaiknya dibagikan hanya lewat kanal yang Anda nyaman dengan enkripsi standar industri (HTTPS) dan pengaturan akun pelanggan.

## Jika ada perbedaan interpretasi

Diskusikan secara tertulis melalui fitur komunikasi yang disediakan. Dokumentasi singkat mengurangi miskomunikasi — terutama saat kondisi bandara atau cuaca berubah.

> **Yang dilindungi:** transaksi di luar alur resmi memutus jejak audit internal kami — sehingga tim dukungan sulit membantu memediasi.

## Menutup

Transparansi bukan slogan; ia adalah produk operasional. Kami merawat titik-titik kontak ini agar pengalaman spiritual Anda tetap prioritas — bukan urusan administrasi yang mengganggu.

MD;
    }

    private static function trustEn(): string
    {
        return <<<'MD'
Companion-led umrah blends human service with digital settlement. Knowing the control points helps you open invoices with confidence.

## What “transparent pricing” really means

Published rates should cover the scope you read on the profile: base days, service mode, and what increments the total (extra pilgrims, extra days, special routes). Optional add-ons must be labelled — not implied at the last mile.

## How checkout behaves

BaytGo links payment intent to the booking request you approved. After a muthowif accepts the slot you selected, you can trace status from your account — the same level of traceability you expect from serious travel marketplaces.

## Privacy and channels

Profiles highlight skills; sensitive pilgrim data should travel through encrypted web sessions and your authenticated inbox — not ad-hoc attachments in unmanaged threads.

## When expectations diverge

Prefer written clarifications through the productized chat surface. A concise paper trail protects both sides when airports, weather, or group pace shift.

> **Protected path:** payments taken outside the official flow detach the internal audit trail — and limit how far support can intervene.

## Closing thought

Operational transparency is not a tagline; it is logistics you can rely on while you focus on the rituals that matter.

MD;
    }

    private static function trustAr(): string
    {
        return <<<'MD'
الشفافية في السعر تعني أن تعرف نطاق الخدمة قبل الالتزام: الأيام، نوع المرافقة، وما يزيد التكلفة بوضوح.

## الدفع عبر المنصة

يحافظ المسار الرسمي على إثبات الحجز ويسهّل المتابعة إذا تغيّر الجدول.

## الخصوصية

تفاصيل الحساسة يفضّل أن تمر عبر قنوات الحساب المعتمدة، وليس المرفقات العشوائية.

> **تنبيه:** الدفع خارج المسار يصعّب حمايتك لاحقًا.

MD;
    }
}
