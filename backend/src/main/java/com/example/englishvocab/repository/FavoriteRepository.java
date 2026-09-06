package com.example.englishvocab.repository;

import com.example.englishvocab.entity.Favorite;
import com.example.englishvocab.entity.FavoriteId;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface FavoriteRepository extends JpaRepository<Favorite, FavoriteId> {
    List<Favorite> findByUser_UserIDOrderByCreatedAtDesc(Integer userId);
    boolean existsByUser_UserIDAndVocabulary_VocabularyID(Integer userId, Integer vocabId);
    void deleteByUser_UserIDAndVocabulary_VocabularyID(Integer userId, Integer vocabId);
}
