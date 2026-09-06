package com.example.englishvocab.service;

import com.example.englishvocab.dto.ApiDtos.*;
import com.example.englishvocab.entity.*;
import com.example.englishvocab.repository.*;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
@Transactional
public class VocabularyService {
    private final VocabularyRepository vocabularyRepository;
    private final TopicRepository topicRepository;
    private final PartOfSpeechRepository posRepository;
    private final VocabularySenseRepository senseRepository;
    private final VocabularyExampleRepository exampleRepository;
    private final FavoriteRepository favoriteRepository;

    public VocabularyService(VocabularyRepository vocabularyRepository, TopicRepository topicRepository,
                             PartOfSpeechRepository posRepository, VocabularySenseRepository senseRepository,
                             VocabularyExampleRepository exampleRepository, FavoriteRepository favoriteRepository) {
        this.vocabularyRepository = vocabularyRepository;
        this.topicRepository = topicRepository;
        this.posRepository = posRepository;
        this.senseRepository = senseRepository;
        this.exampleRepository = exampleRepository;
        this.favoriteRepository = favoriteRepository;
    }

    public List<VocabDto> byTopic(Integer topicId, Integer currentUserId) {
        return vocabularyRepository.findByTopic_TopicIDOrderByWordAsc(topicId)
                .stream().map(v -> toDto(v, currentUserId)).toList();
    }

    public VocabDto get(Integer id, Integer currentUserId) {
        Vocabulary v = vocabularyRepository.findById(id).orElseThrow(() -> new IllegalArgumentException("Không tìm thấy từ"));
        return toDto(v, currentUserId);
    }

    public List<VocabDto> search(String q, Integer currentUserId) {
        return vocabularyRepository.searchByWord(q).stream().map(v -> toDto(v, currentUserId)).toList();
    }

    @Transactional
    public VocabDto create(VocabUpsert req) {
        Topic topic = topicRepository.findById(req.topicId()).orElseThrow(() -> new IllegalArgumentException("Chủ đề không tồn tại"));
        Vocabulary v = new Vocabulary();
        v.setTopic(topic);
        v.setWord(req.word().trim());
        v.setPronunciation(req.pronunciation());
        v.setAudioUrl(req.audioUrl());
        v.setDifficulty(req.difficulty() == null ? "EASY" : req.difficulty());
        vocabularyRepository.save(v);

        PartOfSpeech pos = posRepository.findById(req.partOfSpeechId() == null ? 1 : req.partOfSpeechId())
                .orElseThrow(() -> new IllegalArgumentException("Từ loại không tồn tại"));
        VocabularySense sense = new VocabularySense();
        sense.setVocabulary(v);
        sense.setPartOfSpeech(pos);
        sense.setMeaningVi(req.meaningVi());
        sense.setMeaningEn(req.meaningEn());
        senseRepository.save(sense);

        if (req.exampleEn() != null && !req.exampleEn().isBlank()) {
            VocabularyExample ex = new VocabularyExample();
            ex.setSense(sense);
            ex.setSentenceEn(req.exampleEn());
            ex.setSentenceVi(req.exampleVi());
            exampleRepository.save(ex);
        }
        return toDto(v, null);
    }

    @Transactional
    public VocabDto update(Integer id, VocabUpsert req) {
        Vocabulary v = vocabularyRepository.findById(id).orElseThrow(() -> new IllegalArgumentException("Không tìm thấy từ"));
        if (req.word() != null) v.setWord(req.word().trim());
        if (req.pronunciation() != null) v.setPronunciation(req.pronunciation());
        if (req.audioUrl() != null) v.setAudioUrl(req.audioUrl());
        if (req.difficulty() != null) v.setDifficulty(req.difficulty());
        if (req.topicId() != null) {
            v.setTopic(topicRepository.findById(req.topicId()).orElseThrow(() -> new IllegalArgumentException("Chủ đề không tồn tại")));
        }
        vocabularyRepository.save(v);
        return toDto(v, null);
    }

    @Transactional
    public void delete(Integer id) {
        vocabularyRepository.deleteById(id);
    }

    public VocabDto toDto(Vocabulary v, Integer userId) {
        boolean fav = userId != null && favoriteRepository.existsByUser_UserIDAndVocabulary_VocabularyID(userId, v.getVocabularyID());
        List<SenseDto> senses = senseRepository.findByVocabulary_VocabularyID(v.getVocabularyID()).stream().map(s -> {
            List<ExampleDto> examples = exampleRepository.findBySense_VocabularySenseID(s.getVocabularySenseID())
                    .stream().map(e -> new ExampleDto(e.getExampleID(), e.getSentenceEn(), e.getSentenceVi())).toList();
            return new SenseDto(s.getVocabularySenseID(), s.getPartOfSpeech().getPosCode(),
                    s.getPartOfSpeech().getPartOfSpeechName(), s.getMeaningVi(), s.getMeaningEn(), examples);
        }).toList();
        Integer topicId = v.getTopic().getTopicID();
        String topicName = v.getTopic().getTopicName();
        return new VocabDto(v.getVocabularyID(), topicId, topicName, v.getWord(), v.getPronunciation(),
                v.getAudioUrl(), v.getDifficulty(), fav, senses);
    }
}
