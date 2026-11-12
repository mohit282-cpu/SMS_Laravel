<?php

namespace Database\Seeders;

use App\Models\NepalDistrict;
use App\Models\NepalProvince;
use Illuminate\Database\Seeder;

class NepalGeographySeeder extends Seeder
{
    public function run()
    {
        $provinces = [
            'P1' => ['name' => 'Koshi Province', 'name_ne' => 'कोशी प्रदेश', 'districts' => [
                ['Bhojpur', 'भोजपुर'], ['Dhankuta', 'धनकुटा'], ['Ilam', 'इलाम'], ['Jhapa', 'झापा'],
                ['Khotang', 'खोटाङ'], ['Morang', 'मोरङ'], ['Okhaldhunga', 'ओखलढुङ्गा'], ['Panchthar', 'पाँचथर'],
                ['Sankhuwasabha', 'सङ्खुवासभा'], ['Solukhumbu', 'सोलुखुम्बु'], ['Sunsari', 'सुनसरी'], ['Taplejung', 'ताप्लेजुङ'],
                ['Terhathum', 'तेह्रथुम'], ['Udayapur', 'उदयपुर'],
            ]],
            'P2' => ['name' => 'Madhesh Province', 'name_ne' => 'मधेश प्रदेश', 'districts' => [
                ['Bara', 'बारा'], ['Dhanusha', 'धनुषा'], ['Mahottari', 'महोत्तरी'], ['Parsa', 'पर्सा'],
                ['Rautahat', 'रौतहट'], ['Saptari', 'सप्तरी'], ['Sarlahi', 'सर्लाही'], ['Siraha', 'सिराहा'],
            ]],
            'P3' => ['name' => 'Bagmati Province', 'name_ne' => 'बागमती प्रदेश', 'districts' => [
                ['Bhaktapur', 'भक्तपुर'], ['Chitwan', 'चितवन'], ['Dhading', 'धादिङ'], ['Dolakha', 'दोलखा'],
                ['Kathmandu', 'काठमाडौँ'], ['Kavrepalanchok', 'काभ्रेपलाञ्चोक'], ['Lalitpur', 'ललितपुर'], ['Makwanpur', 'मकवानपुर'],
                ['Nuwakot', 'नुवाकोट'], ['Ramechhap', 'रामेछाप'], ['Rasuwa', 'रसुवा'], ['Sindhuli', 'सिन्धुली'],
                ['Sindhupalchok', 'सिन्धुपाल्चोक'],
            ]],
            'P4' => ['name' => 'Gandaki Province', 'name_ne' => 'गण्डकी प्रदेश', 'districts' => [
                ['Baglung', 'बागलुङ'], ['Gorkha', 'गोरखा'], ['Kaski', 'कास्की'], ['Lamjung', 'लमजुङ'],
                ['Manang', 'मनाङ'], ['Mustang', 'मुस्ताङ'], ['Myagdi', 'म्याग्दी'], ['Nawalparasi East', 'नवलपरासी (बर्दघाट सुस्ता पूर्व)'],
                ['Parbat', 'पर्वत'], ['Syangja', 'स्याङ्जा'], ['Tanahun', 'तनहुँ'],
            ]],
            'P5' => ['name' => 'Lumbini Province', 'name_ne' => 'लुम्बिनी प्रदेश', 'districts' => [
                ['Arghakhanchi', 'अर्घाखाँची'], ['Banke', 'बाँके'], ['Bardiya', 'बर्दिया'], ['Dang', 'दाङ'],
                ['Gulmi', 'गुल्मी'], ['Kapilvastu', 'कपिलवस्तु'], ['Nawalparasi West', 'नवलपरासी (बर्दघाट सुस्ता पश्चिम)'], ['Palpa', 'पाल्पा'],
                ['Pyuthan', 'प्यूठान'], ['Rolpa', 'रोल्पा'], ['Rukum East', 'रुकुम (पूर्वी भाग)'], ['Rupandehi', 'रूपन्देही'],
            ]],
            'P6' => ['name' => 'Karnali Province', 'name_ne' => 'कर्णाली प्रदेश', 'districts' => [
                ['Dailekh', 'दैलेख'], ['Dolpa', 'डोल्पा'], ['Humla', 'हुम्ला'], ['Jajarkot', 'जाजरकोट'],
                ['Jumla', 'जुम्ला'], ['Kalikot', 'कालिकोट'], ['Mugu', 'मुगु'], ['Rukum West', 'रुकुम (पश्चिमी भाग)'],
                ['Salyan', 'सल्यान'], ['Surkhet', 'सुर्खेत'],
            ]],
            'P7' => ['name' => 'Sudurpashchim Province', 'name_ne' => 'सुदूरपश्चिम प्रदेश', 'districts' => [
                ['Achham', 'अछाम'], ['Baitadi', 'बैतडी'], ['Bajhang', 'बझाङ'], ['Bajura', 'बाजुरा'],
                ['Dadeldhura', 'डडेलधुरा'], ['Darchula', 'दार्चुला'], ['Doti', 'डोटी'], ['Kailali', 'कैलाली'],
                ['Kanchanpur', 'कञ्चनपुर'],
            ]],
        ];

        foreach ($provinces as $code => $pData) {
            $province = NepalProvince::updateOrCreate(
                ['code' => $code],
                ['name' => $pData['name'], 'name_ne' => $pData['name_ne']]
            );

            foreach ($pData['districts'] as [$dName, $dNameNe]) {
                NepalDistrict::updateOrCreate(
                    ['province_id' => $province->id, 'name' => $dName],
                    ['name_ne' => $dNameNe]
                );
            }
        }
    }
}

