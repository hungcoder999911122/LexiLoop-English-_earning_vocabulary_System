package com.example.englishvocab.service;

import com.example.englishvocab.dto.ApiDtos.VocabDto;
import com.example.englishvocab.entity.Favorite;
import com.example.englishvocab.entity.User;
import com.example.englishvocab.entity.Vocabulary;
import com.example.englishvocab.repository.FavoriteRepository;
import com.example.englishvocab.repository.VocabularyRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
public class FavoriteService {
    private final FavoriteRepository favoriteRepository;
    private final VocabularyRepository vocabularyRepository;
    private final VocabularyService vocabularyService;

    public FavoriteService(FavoriteRepository favoriteRepository, VocabularyRepository vocabularyRepository,
                           VocabularyService vocabularyService) {
        this.favoriteRepository = favoriteRepository;
        this.vocabularyRepository = vocabularyRepository;
        this.vocabularyService = vocabularyService;
    }

    public List<VocabDto> list(User user) {
        return favoriteRepository.findByUser_UserIDOrderByCreatedAtDesc(user.getUserID())
                .stream().map(f -> vocabularyService.toDto(f.getVocabulary(), user.getUserID())).toList();
    }

    @Transactional
    public void add(User user, Integer vocabId) {
        if (favoriteRepository.existsByUser_UserIDAndVocabulary_VocabularyID(user.getUserID(), vocabId)) {
            return;
        }
        Vocabulary v = vocabularyRepository.findById(vocabId).orElseThrow(() -> new IllegalArgumentException("Không tìm thấy từ"));
        Favorite f = new Favorite();
        f.setUser(user);
        f.setVocabulary(v);
        favoriteRepository.save(f);
    }

    @Transactional
    public void remove(User user, Integer vocabId) {
        favoriteRepository.deleteByUser_UserIDAndVocabulary_VocabularyID(user.getUserID(), vocabId);
    }
}
