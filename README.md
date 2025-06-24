# Gravity Forms Iyzico Integration

*[Türkçe dokümantasyon için aşağıya bakın](#türkçe-dokümantasyon)*

A comprehensive WordPress plugin that integrates Gravity Forms with Iyzico payment gateway, enabling secure one-time payments with advanced verification features.

## 🚀 Overview

This add-on plugin extends Gravity Forms functionality by adding Iyzico payment processing capabilities. Users can create payment forms that automatically redirect to Iyzico's secure checkout, process payments, and verify transaction results with customizable confirmation pages.

## ✨ Key Features

- **🔐 Secure Payment Processing** - Direct integration with Iyzico's official API
- **📋 Form-Based Configuration** - Individual feed setup for each form
- **🔄 Automatic Redirects** - Seamless user experience from form to payment
- **✅ Payment Verification** - Built-in transaction validation and confirmation
- **🎨 Customizable Success Pages** - Flexible shortcode system for result pages
- **🛡️ Security Features** - Token-based verification and expiration controls
- **🌍 Multi-Language** - Full Turkish and English support
- **📱 Responsive Design** - Works on all devices and screen sizes

## 📋 Requirements

- WordPress 5.0 or higher
- Gravity Forms 2.5 or higher
- PHP 7.4 or higher
- Active Iyzico merchant account
- SSL certificate (required for payment processing)

## 🔧 Installation

1. **Install Gravity Forms** (if not already installed)
2. **Upload this plugin** to `/wp-content/plugins/gravityforms-iyzico/`
3. **Activate the plugin** through WordPress admin
4. **Configure Iyzico settings** in Forms → Settings → Iyzico
5. **Set up individual form feeds** for payment processing

## ⚙️ Configuration

### 1. Global Plugin Settings
Navigate to **Forms → Settings → Iyzico** and configure:

- **API Key** - Your Iyzico API key
- **Secret Key** - Your Iyzico secret key  
- **Mode** - Sandbox (testing) or Production
- **Enable Redirect** - Auto-redirect to payment after form submission

### 2. Form Feed Setup
For each payment form:

1. **Edit your form** → **Settings → Iyzico**
2. **Create new feed** with unique name
3. **Map customer fields** (email, name, phone, etc.)
4. **Set payment amount** (form total or specific field)
5. **Choose redirect page** for post-payment confirmation
6. **Configure conditional logic** (if needed)

### 3. Payment Confirmation Page
Create a WordPress page for payment results and add the shortcode:

```
[iyzico_teyit]
```

## 📋 Shortcode Usage

### Basic Usage

```
[iyzico_teyit]
```

This shortcode displays payment verification results with default settings.

### Advanced Usage

```
[iyzico_teyit mask_email="no" expire_minutes="120" redirect_delay="10"]
```

## ⚙️ Shortcode Parameters

| Parameter | Default | Description | Values |
|-----------|---------|-------------|--------|
| `mask_email` | `"yes"` | Mask email addresses in display | `"yes"`, `"no"` |
| `expire_minutes` | `"60"` | Link expiration time (minutes) | Any number |
| `redirect_delay` | `"5"` | Auto-redirect delay (seconds) | Any number |
| `show_default` | `"yes"` | Show default success/error messages | `"yes"`, `"no"` |
| `require_token` | `"yes"` | Require security token validation | `"yes"`, `"no"` |

## 🚀 Usage Examples

### Quick Confirmation Page
```
[iyzico_teyit expire_minutes="30" redirect_delay="3"]
```
Quick validation with 30-minute link expiry and 3-second redirect.

### Detailed Information Page
```
[iyzico_teyit mask_email="no" show_default="yes" redirect_delay="0"]
```
Show full email addresses, default messages, immediate redirect.

### High Security Setup
```
[iyzico_teyit require_token="yes" expire_minutes="1440"]
```
Maximum security with required tokens and 24-hour validity.

## 🔄 Payment Flow

1. **User fills form** → Form submission triggers Iyzico integration
2. **Automatic redirect** → User sent to Iyzico secure checkout
3. **Payment processing** → Iyzico handles payment securely
4. **Callback verification** → Plugin verifies transaction status
5. **Result display** → User sees confirmation on your site

## 🛡️ Security Features

- **Token-based verification** - Prevents unauthorized access
- **Expiring links** - Time-limited payment verification URLs
- **Callback validation** - Server-side transaction verification
- **Entry-specific tokens** - Unique security tokens per transaction
- **IP logging** - Track payment attempts for security

## 🔍 Troubleshooting

### Payment Not Processing
- Verify API keys in plugin settings
- Check form feed configuration
- Ensure SSL is active on your site
- Confirm Iyzico account is active

### Redirect Issues
- Verify redirect page exists and is published
- Check shortcode is added to confirmation page
- Ensure feed settings point to correct page

### Security Errors
- Check token expiration settings
- Verify URL parameters are intact
- Confirm require_token setting matches usage

### Form Not Showing Iyzico Option
- Verify plugin is activated
- Check Gravity Forms version compatibility
- Ensure feed is created and active for the form

## 📞 Support

For technical support:
- Check WordPress debug logs
- Verify Iyzico API responses
- Review plugin error logs
- Consult Iyzico developer documentation

---

# Türkçe Dokümantasyon

Gravity Forms ile Iyzico ödeme sistemini entegre eden, güvenli tek seferlik ödemeler için gelişmiş doğrulama özellikleri sunan kapsamlı bir WordPress eklentisi.

## 🚀 Genel Bakış

Bu eklenti, Gravity Forms'a Iyzico ödeme işleme yetenekleri ekleyerek genişletir. Kullanıcılar, otomatik olarak Iyzico'nun güvenli ödeme sayfasına yönlendiren, ödemeleri işleyen ve özelleştirilebilir onay sayfalarıyla işlem sonuçlarını doğrulayan ödeme formları oluşturabilir.

## ✨ Temel Özellikler

- **🔐 Güvenli Ödeme İşleme** - Iyzico resmi API'si ile doğrudan entegrasyon
- **📋 Form Tabanlı Yapılandırma** - Her form için ayrı feed kurulumu
- **🔄 Otomatik Yönlendirmeler** - Formdan ödemeye kesintisiz kullanıcı deneyimi
- **✅ Ödeme Doğrulama** - Yerleşik işlem doğrulama ve onay
- **🎨 Özelleştirilebilir Başarı Sayfaları** - Sonuç sayfaları için esnek kısa kod sistemi
- **🛡️ Güvenlik Özellikleri** - Token tabanlı doğrulama ve süre kontrolleri
- **🌍 Çok Dilli** - Tam Türkçe ve İngilizce desteği
- **📱 Duyarlı Tasarım** - Tüm cihaz ve ekran boyutlarında çalışır

## 📋 Gereksinimler

- WordPress 5.0 veya üzeri
- Gravity Forms 2.5 veya üzeri
- PHP 7.4 veya üzeri
- Aktif Iyzico üye işyeri hesabı
- SSL sertifikası (ödeme işleme için gerekli)

## 🔧 Kurulum

1. **Gravity Forms'u kurun** (henüz kurulu değilse)
2. **Bu eklentiyi yükleyin** `/wp-content/plugins/gravityforms-iyzico/` dizinine
3. **Eklentiyi etkinleştirin** WordPress yönetim panelinden
4. **Iyzico ayarlarını yapılandırın** Forms → Settings → Iyzico'da
5. **Ödeme işleme için bireysel form feedleri** kurun

## ⚙️ Yapılandırma

### 1. Global Eklenti Ayarları
**Forms → Settings → Iyzico**'ya gidin ve yapılandırın:

- **API Key** - Iyzico API anahtarınız
- **Secret Key** - Iyzico gizli anahtarınız
- **Mode** - Sandbox (test) veya Production
- **Enable Redirect** - Form gönderiminden sonra otomatik ödeme yönlendirmesi

### 2. Form Feed Kurulumu
Her ödeme formu için:

1. **Formunuzu düzenleyin** → **Settings → Iyzico**
2. **Benzersiz adla yeni feed oluşturun**
3. **Müşteri alanlarını eşleştirin** (e-posta, ad, telefon, vb.)
4. **Ödeme tutarını belirleyin** (form toplamı veya belirli alan)
5. **Ödeme sonrası onay için yönlendirme sayfası seçin**
6. **Koşullu mantığı yapılandırın** (gerekirse)

### 3. Ödeme Onay Sayfası
Ödeme sonuçları için bir WordPress sayfası oluşturun ve kısa kodu ekleyin:

```
[iyzico_teyit]
```

## 📋 Kısa Kod Kullanımı

### Temel Kullanım

```
[iyzico_teyit]
```

Bu kısa kod, varsayılan ayarlarla ödeme doğrulama sonuçlarını görüntüler.

### Gelişmiş Kullanım

```
[iyzico_teyit mask_email="no" expire_minutes="120" redirect_delay="10"]
```

## ⚙️ Kısa Kod Parametreleri

| Parametre | Varsayılan | Açıklama | Değerler |
|-----------|------------|----------|----------|
| `mask_email` | `"yes"` | E-posta adreslerini ekranda maskele | `"yes"`, `"no"` |
| `expire_minutes` | `"60"` | Link sona erme süresi (dakika) | Herhangi bir sayı |
| `redirect_delay` | `"5"` | Otomatik yönlendirme gecikmesi (saniye) | Herhangi bir sayı |
| `show_default` | `"yes"` | Varsayılan başarı/hata mesajlarını göster | `"yes"`, `"no"` |
| `require_token` | `"yes"` | Güvenlik anahtarı doğrulaması gerekli | `"yes"`, `"no"` |

## 🚀 Kullanım Örnekleri

### Hızlı Onay Sayfası
```
[iyzico_teyit expire_minutes="30" redirect_delay="3"]
```
30 dakika link süresi ve 3 saniye yönlendirme ile hızlı doğrulama.

### Detaylı Bilgi Sayfası
```
[iyzico_teyit mask_email="no" show_default="yes" redirect_delay="0"]
```
Tam e-posta adresleri, varsayılan mesajlar, anında yönlendirme.

### Yüksek Güvenlik Kurulumu
```
[iyzico_teyit require_token="yes" expire_minutes="1440"]
```
Gerekli tokenlar ve 24 saat geçerlilik ile maksimum güvenlik.

## 🔄 Ödeme Akışı

1. **Kullanıcı formu doldurur** → Form gönderimi Iyzico entegrasyonunu tetikler
2. **Otomatik yönlendirme** → Kullanıcı Iyzico güvenli ödeme sayfasına gönderilir
3. **Ödeme işleme** → Iyzico ödemeyi güvenli şekilde işler
4. **Callback doğrulama** → Eklenti işlem durumunu doğrular
5. **Sonuç gösterimi** → Kullanıcı sitenizde onay görür

## 🛡️ Güvenlik Özellikleri

- **Token tabanlı doğrulama** - Yetkisiz erişimi önler
- **Süreli linkler** - Zaman sınırlı ödeme doğrulama URL'leri
- **Callback doğrulama** - Sunucu tarafı işlem doğrulaması
- **Kayıt-özel tokenlar** - İşlem başına benzersiz güvenlik tokenları
- **IP günlükleme** - Güvenlik için ödeme denemelerini izler

## 🔍 Sorun Giderme

### Ödeme İşlenmiyor
- Eklenti ayarlarındaki API anahtarlarını doğrulayın
- Form feed yapılandırmasını kontrol edin
- Sitenizde SSL'in aktif olduğundan emin olun
- Iyzico hesabınızın aktif olduğunu onaylayın

### Yönlendirme Sorunları
- Yönlendirme sayfasının mevcut ve yayınlanmış olduğunu doğrulayın
- Onay sayfasına kısa kodun eklendiğini kontrol edin
- Feed ayarlarının doğru sayfayı işaret ettiğinden emin olun

### Güvenlik Hataları
- Token sona erme ayarlarını kontrol edin
- URL parametrelerinin bozulmadığını doğrulayın
- require_token ayarının kullanımla eşleştiğini onaylayın

### Form Iyzico Seçeneğini Göstermiyor
- Eklentinin etkinleştirildiğini doğrulayın
- Gravity Forms sürüm uyumluluğunu kontrol edin
- Form için feed'in oluşturulduğunu ve aktif olduğunu emin olun

## 📞 Destek

Teknik destek için:
- WordPress hata loglarını kontrol edin
- Iyzico API yanıtlarını doğrulayın
- Eklenti hata loglarını inceleyin
- Iyzico geliştirici dokümantasyonuna başvurun

---

**Version:** 1.0  
**Requires:** WordPress 5.0+, Gravity Forms 2.5+  
**License:** GPL v2 or later