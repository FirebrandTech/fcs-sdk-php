<?php

namespace Fcs;

class AssetTypes
{
    const Epub = 'CLD_AT_Epub';
    const Pdf = 'CLD_AT_WebPdf';
    const Kindle = 'CLD_AT_Kindle';
    const PublisherKindle = 'CLD_AT_PublisherKindle';
    const Cover = 'CLD_AT_CoverArtHigh';
    const TDrm = 'CLD_AT_TDrm'; // Temporary Protected (ACS) - expires after 55 days
    const TDrmEpub = 'CLD_AT_TDrmEpub'; // Temporary Protected EPUB (ACS) - expires after 55 days
    const TDrmPdf = 'CLD_AT_TDrmPdf'; // Temporary Protected PDF (ACS) - expires after 55 days
    const PDrm = 'CLD_AT_PDrm'; // Permanent Protected (ACS)
    const PDrmEpub = 'CLD_AT_PDrmEpub'; // Permanent Protected EPUB (ACS)
    const PDrmPdf = 'CLD_AT_PDrmPdf'; // Permanent Protected PDF (ACS)
    const EDrmEpub = 'CLD_AT_EDrmEpub'; // Enthrill social DRMed EPUB
    const EDrmPdf = 'CLD_AT_EDrmPdf'; // Enthrill social DRMed PDF
    const LcpDrmEpub = 'CLD_AT_LcpDrmEpub'; // LCP Protected EPUB
    const LcpDrmPdf = 'CLD_AT_LcpDrmPdf'; // LCP Protected PDF
    const Mp3Full = 'CLD_AT_AudioMP3';
    const Mp3Excerpt = 'CLD_AT_Sample_MP3';
    const FullAudio = 'CLD_AT_W3CFullAudioZip';
    const IndexedAudio = 'CLD_AT_ZipMP3WithManifest';
    const StreamingAudio = 'CLD_AT_StrmblAudioBook';
}
