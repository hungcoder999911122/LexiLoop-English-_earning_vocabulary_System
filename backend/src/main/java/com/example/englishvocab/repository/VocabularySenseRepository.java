package com.example.englishvocab.repository;

import com.example.englishvocab.entity.VocabularySense;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface VocabularySenseRepository extends JpaRepository<VocabularySense, Integer> {
    List<VocabularySense> findByVocabulary_VocabularyID(Integer vocabularyId);
}
