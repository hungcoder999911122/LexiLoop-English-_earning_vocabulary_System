package com.example.englishvocab.repository;

import com.example.englishvocab.entity.LearningProgress;
import org.springframework.data.jpa.repository.JpaRepository;

import java.time.LocalDate;
import java.util.List;
import java.util.Optional;

public interface LearningProgressRepository extends JpaRepository<LearningProgress, Integer> {
    Optional<LearningProgress> findByUser_UserIDAndVocabulary_VocabularyID(Integer userId, Integer vocabId);

    List<LearningProgress> findByUser_UserIDAndNextReviewDateLessThanEqual(Integer userId, LocalDate date);

    List<LearningProgress> findByUser_UserID(Integer userId);
}
